<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Coupon;
use App\Models\Cart;
use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;
use App\Traits\ApiResponse;
use App\Mail\OrderStatusUpdatedMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    use ApiResponse;

    // Storefront: Checkout & Place Order
    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.size' => ['nullable', 'string', 'max:50'],
            'items.*.color' => ['nullable', 'string', 'max:50'],
            'coupon_code' => ['nullable', 'string'],
            
            // Shipping details
            'shipping_name' => ['required', 'string', 'max:255'],
            'shipping_email' => ['required', 'email', 'max:255'],
            'shipping_phone' => ['nullable', 'string', 'max:50'],
            'shipping_address' => ['required', 'string', 'max:500'],
            'shipping_city' => ['required', 'string', 'max:100'],
            'shipping_postal_code' => ['nullable', 'string', 'max:20'],
            'shipping_country' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $user = $request->user();
        if (!$user && $token = $request->bearerToken()) {
            $user = PersonalAccessToken::findToken($token)?->tokenable;
        }

        // Wrap in DB transaction with row locks to prevent overselling
        return DB::transaction(function () use ($validated, $user, $request) {
            $subtotal = 0;
            $orderItemsData = [];

            // 1. Validate stock and compute subtotal securely on the backend
            foreach ($validated['items'] as $item) {
                // Lock row for update
                $product = Product::where('id', $item['product_id'])->lockForUpdate()->firstOrFail();

                if (!$product->is_active) {
                    return $this->error("Product '{$product->name}' is currently unavailable.", 422);
                }

                if ($product->stock < $item['quantity']) {
                    return $this->error(
                        "Insufficient stock for '{$product->name}'. Only {$product->stock} available.",
                        422
                    );
                }

                $itemPrice = $product->effective_price;
                $lineTotal = $itemPrice * $item['quantity'];
                $subtotal += $lineTotal;

                $orderItemsData[] = [
                    'product' => $product,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'price' => $itemPrice,
                    'quantity' => $item['quantity'],
                    'size' => $item['size'] ?? null,
                    'color' => $item['color'] ?? null,
                    'subtotal' => $lineTotal,
                ];
            }

            // 2. Validate coupon and calculate discount on backend
            $discountAmount = 0;
            $appliedCoupon = null;

            if (!empty($validated['coupon_code'])) {
                $coupon = Coupon::where('code', strtoupper(trim($validated['coupon_code'])))->first();
                if ($coupon) {
                    $check = $coupon->isValidForAmount($subtotal);
                    if ($check['valid']) {
                        $discountAmount = $coupon->calculateDiscount($subtotal);
                        $appliedCoupon = $coupon;
                        $coupon->increment('used_count');
                    }
                }
            }

            // 3. Delivery fee: $15 standard, free over $250
            $deliveryFee = $subtotal >= 250 ? 0 : 15;
            $totalAmount = max(0, $subtotal - $discountAmount + $deliveryFee);

            // 4. Create Order
            $order = Order::create([
                'order_number' => Order::generateOrderNumber(),
                'user_id' => $user?->id,
                'status' => 'pending',
                'payment_status' => 'pending',
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'delivery_fee' => $deliveryFee,
                'total_amount' => $totalAmount,
                'coupon_id' => $appliedCoupon?->id,
                'coupon_code' => $appliedCoupon?->code,
                'shipping_name' => $validated['shipping_name'],
                'shipping_email' => $validated['shipping_email'],
                'shipping_phone' => $validated['shipping_phone'] ?? null,
                'shipping_address' => $validated['shipping_address'],
                'shipping_city' => $validated['shipping_city'],
                'shipping_postal_code' => $validated['shipping_postal_code'] ?? null,
                'shipping_country' => $validated['shipping_country'] ?? 'United States',
                'notes' => $validated['notes'] ?? null,
            ]);

            // 5. Create Order Items (Note: Stock is decremented upon payment confirmation per business requirement)
            foreach ($orderItemsData as $itemData) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $itemData['product_id'],
                    'product_name' => $itemData['product_name'],
                    'price' => $itemData['price'],
                    'quantity' => $itemData['quantity'],
                    'size' => $itemData['size'],
                    'color' => $itemData['color'],
                    'subtotal' => $itemData['subtotal'],
                ]);
            }

            // 6. Clear cart if user has cart or guest token
            $guestToken = $request->header('X-Guest-Token');
            if ($user) {
                Cart::where('user_id', $user->id)->first()?->items()->delete();
            } elseif ($guestToken) {
                Cart::where('guest_token', $guestToken)->first()?->items()->delete();
            }

            return $this->success([
                'order' => $order->load(['items.product.images', 'coupon']),
            ], 'Order placed successfully.', 201);
        });
    }

    // Storefront: Customer's own orders list
    public function myOrders(Request $request)
    {
        $user = $request->user();
        if (!$user && $token = $request->bearerToken()) {
            $user = PersonalAccessToken::findToken($token)?->tokenable;
        }

        if (!$user) {
            return $this->error('Unauthenticated.', 401);
        }

        $query = Order::query();

        // If staff/admin is inspecting a specific customer:
        $isStaff = $user->isSuperAdmin() || $user->hasPermission('orders.view');
        if ($isStaff && $request->filled('customer_id')) {
            $query->where('user_id', $request->customer_id);
        } elseif ($isStaff && $request->filled('email')) {
            $query->where('shipping_email', strtolower(trim($request->email)));
        } else {
            // Customer view: only their own orders
            $query->where('user_id', $user->id);
        }

        $orders = $query->with(['items.product.images'])
            ->latest()
            ->paginate($request->get('per_page', 20));

        return $this->paginated($orders);
    }

    // Storefront: Show order details (with IDOR protection)
    public function show(Request $request, $id)
    {
        $order = Order::with(['items.product.images', 'payment'])
            ->where(function ($q) use ($id) {
                $q->where('id', $id)->orWhere('order_number', $id);
            })->firstOrFail();

        $user = $request->user();
        if (!$user && $token = $request->bearerToken()) {
            $user = PersonalAccessToken::findToken($token)?->tokenable;
        }

        // IDOR protection:
        // 1. Staff with orders.view or super admin can always view
        // 2. The order owner (by user_id) can view
        // 3. Guests providing matching order_number can view their receipt
        $isStaff = $user && ($user->isSuperAdmin() || $user->hasPermission('orders.view'));
        $isOwner = $user && ($order->user_id === $user->id);

        if ($user) {
            if (!$isOwner && !$isStaff) {
                return $this->error('Unauthorized to view this order.', 403);
            }
        } else {
            // For guest checkout confirmation by order_number
            if ($request->get('order_number') !== $order->order_number && $id !== $order->order_number) {
                return $this->error('Unauthorized.', 403);
            }
        }

        return $this->success($order);
    }

    // Admin: List all orders with filters
    public function adminIndex(Request $request)
    {
        $query = Order::with(['user', 'items']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('order_number', 'like', "%{$s}%")
                  ->orWhere('shipping_name', 'like', "%{$s}%")
                  ->orWhere('shipping_email', 'like', "%{$s}%");
            });
        }

        $orders = $query->latest()->paginate($request->get('per_page', 20));

        return $this->paginated($orders);
    }

    // Admin: Get single order details
    public function adminShow($id)
    {
        $order = Order::with(['user', 'items.product.images', 'payment', 'coupon'])->findOrFail($id);
        return $this->success($order);
    }

    // Admin: Update order status & restore stock on cancellation
    public function updateStatus(Request $request, $id)
    {
        $order = Order::with('items.product')->findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', 'in:pending,paid,processing,shipped,delivered,cancelled'],
        ]);

        $newStatus = $validated['status'];
        $oldStatus = $order->status;

        if ($oldStatus === $newStatus) {
            return $this->success($order, 'Status already set.');
        }

        DB::transaction(function () use ($order, $newStatus, $oldStatus) {
            // Requirement: Stock is decremented only when paid
            if ($newStatus === 'paid' && $oldStatus !== 'paid') {
                foreach ($order->items as $item) {
                    $product = Product::where('id', $item->product_id)->lockForUpdate()->firstOrFail();
                    if ($product->stock < $item->quantity) {
                        throw new \Exception("Insufficient stock for '{$product->name}'. Only {$product->stock} available.");
                    }
                    $product->decrement('stock', $item->quantity);
                }
                $order->paid_at = now();
                $order->payment_status = 'paid';
            }

            // Requirement: Cancelled orders restore stock if it was previously decremented
            if ($newStatus === 'cancelled' && $oldStatus !== 'cancelled') {
                if (in_array($oldStatus, ['paid', 'processing', 'shipped'])) {
                    foreach ($order->items as $item) {
                        if ($item->product) {
                            $item->product->increment('stock', $item->quantity);
                        }
                    }
                }
                $order->cancelled_at = now();
            }

            $order->status = $newStatus;
            $order->save();
        });

        // Trigger order status update email to customer via Mailhog/Mailpit
        try {
            if ($order->shipping_email) {
                Mail::to($order->shipping_email)->send(new OrderStatusUpdatedMail($order->fresh(['items']), $oldStatus));
            }
        } catch (\Exception $e) {
            Log::warning('Failed sending order status update email: ' . $e->getMessage());
        }

        return $this->success($order->fresh(), "Order status updated to {$newStatus}.");
    }
}
