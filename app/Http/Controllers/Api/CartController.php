<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CartController extends Controller
{
    use ApiResponse;

    protected function getOrCreateCart(Request $request): Cart
    {
        $user = $request->user();

        if ($user) {
            $cart = Cart::firstOrCreate(['user_id' => $user->id]);
        } else {
            $guestToken = $request->header('X-Guest-Token') ?: $request->input('guest_token');
            if (!$guestToken) {
                $guestToken = Str::uuid()->toString();
            }

            $cart = Cart::firstOrCreate(
                ['guest_token' => $guestToken],
                ['expires_at' => now()->addDays(30)]
            );
        }

        return $cart->load(['items.product.images']);
    }

    public function index(Request $request)
    {
        $cart = $this->getOrCreateCart($request);

        return $this->success([
            'id' => $cart->id,
            'guest_token' => $cart->guest_token,
            'items' => $cart->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'name' => $item->product->name,
                    'price' => (float) $item->price,
                    'size' => $item->size,
                    'color' => $item->color,
                    'quantity' => $item->quantity,
                    'subtotal' => (float) ($item->price * $item->quantity),
                    'stock' => $item->product->stock,
                    'image' => $item->product->images->first()->image_url ?? null,
                ];
            }),
            'subtotal' => $cart->subtotal,
            'total_count' => $cart->total_count,
        ]);
    }

    public function addItem(Request $request)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'size' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:50'],
        ]);

        $product = Product::active()->findOrFail($validated['product_id']);

        // Requirement: Out-of-stock products cannot be added to cart
        if ($product->stock <= 0) {
            return $this->error("Sorry, '{$product->name}' is out of stock.", 422);
        }

        $cart = $this->getOrCreateCart($request);

        $existingItem = $cart->items()
            ->where('product_id', $product->id)
            ->where('size', $validated['size'] ?? null)
            ->where('color', $validated['color'] ?? null)
            ->first();

        $desiredQuantity = ($existingItem ? $existingItem->quantity : 0) + $validated['quantity'];

        if ($desiredQuantity > $product->stock) {
            return $this->error("Only {$product->stock} items available in stock.", 422);
        }

        if ($existingItem) {
            $existingItem->update([
                'quantity' => $desiredQuantity,
                'price' => $product->effective_price,
            ]);
        } else {
            $cart->items()->create([
                'product_id' => $product->id,
                'size' => $validated['size'] ?? null,
                'color' => $validated['color'] ?? null,
                'quantity' => $validated['quantity'],
                'price' => $product->effective_price,
            ]);
        }

        return $this->index($request);
    }

    public function updateItem(Request $request, $id)
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $cart = $this->getOrCreateCart($request);
        $item = $cart->items()->findOrFail($id);

        if ($validated['quantity'] > $item->product->stock) {
            return $this->error("Only {$item->product->stock} units available in stock.", 422);
        }

        $item->update([
            'quantity' => $validated['quantity'],
            'price' => $item->product->effective_price,
        ]);

        return $this->index($request);
    }

    public function removeItem(Request $request, $id)
    {
        $cart = $this->getOrCreateCart($request);
        $cart->items()->where('id', $id)->delete();

        return $this->index($request);
    }

    public function clear(Request $request)
    {
        $cart = $this->getOrCreateCart($request);
        $cart->items()->delete();

        return $this->index($request);
    }

    // Merge guest cart on login
    public function merge(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return $this->error('Authentication required to merge cart.', 401);
        }

        $guestToken = $request->input('guest_token') ?: $request->header('X-Guest-Token');
        if (!$guestToken) {
            return $this->index($request);
        }

        $guestCart = Cart::where('guest_token', $guestToken)->with('items')->first();
        if (!$guestCart || $guestCart->items->isEmpty()) {
            return $this->index($request);
        }

        $userCart = Cart::firstOrCreate(['user_id' => $user->id]);

        foreach ($guestCart->items as $guestItem) {
            $userItem = $userCart->items()
                ->where('product_id', $guestItem->product_id)
                ->where('size', $guestItem->size)
                ->where('color', $guestItem->color)
                ->first();

            if ($userItem) {
                $userItem->increment('quantity', $guestItem->quantity);
            } else {
                $userCart->items()->create([
                    'product_id' => $guestItem->product_id,
                    'size' => $guestItem->size,
                    'color' => $guestItem->color,
                    'quantity' => $guestItem->quantity,
                    'price' => $guestItem->price,
                ]);
            }
        }

        // Delete guest cart after merge
        $guestCart->items()->delete();
        $guestCart->delete();

        return $this->index($request);
    }
}
