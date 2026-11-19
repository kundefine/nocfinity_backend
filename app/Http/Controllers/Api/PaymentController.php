<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Mail\OrderStatusUpdatedMail;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    use ApiResponse;

    // Create Stripe PaymentIntent
    public function createPaymentIntent(Request $request)
    {
        $validated = $request->validate([
            'order_id' => ['required', 'exists:orders,id'],
        ]);

        $order = Order::findOrFail($validated['order_id']);

        if ($order->status === 'paid') {
            return $this->error('Order has already been paid.', 400);
        }

        $stripeSecret = config('services.stripe.secret') ?? env('STRIPE_SECRET') ?? env('STRIPE_SECRET_KEY');

        try {
            if ($stripeSecret && !str_starts_with($stripeSecret, 'sk_test_placeholder')) {
                Stripe::setApiKey($stripeSecret);

                $paymentIntent = PaymentIntent::create([
                    'amount' => (int) round($order->total_amount * 100), // in cents
                    'currency' => 'usd',
                    'metadata' => [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                    ],
                ]);

                $clientSecret = $paymentIntent->client_secret;
                $intentId = $paymentIntent->id;
            } else {
                // Mock test mode fallback for local sandbox / test without live credentials
                $intentId = 'pi_test_' . Str::random(24);
                $clientSecret = "{$intentId}_secret_" . Str::random(16);
            }

            // Create or update payment record
            Payment::updateOrCreate(
                ['order_id' => $order->id],
                [
                    'payment_method' => 'stripe',
                    'transaction_id' => $intentId,
                    'status' => 'pending',
                    'amount' => $order->total_amount,
                    'currency' => 'usd',
                    'raw_response' => ['intent_id' => $intentId],
                ]
            );

            return $this->success([
                'client_secret' => $clientSecret,
                'transaction_id' => $intentId,
                'amount' => $order->total_amount,
                'currency' => 'usd',
                'order_number' => $order->order_number,
            ]);
        } catch (\Exception $e) {
            return $this->error('Stripe error: ' . $e->getMessage(), 500);
        }
    }

    // Confirm Payment
    public function confirmPayment(Request $request)
    {
        $validated = $request->validate([
            'order_id' => ['required', 'exists:orders,id'],
            'transaction_id' => ['required', 'string'],
            'status' => ['required', 'in:succeeded,failed'],
        ]);

        $order = Order::with('items')->findOrFail($validated['order_id']);

        if ($validated['status'] === 'succeeded') {
            try {
                DB::transaction(function () use ($order, $validated) {
                    // Prevent overselling: check & decrement stock when paid using row locks
                    foreach ($order->items as $item) {
                        $product = Product::where('id', $item->product_id)->lockForUpdate()->firstOrFail();
                        if ($product->stock < $item->quantity) {
                            throw new \Exception("Stock changed before payment completed for '{$product->name}'. Only {$product->stock} available.");
                        }
                        $product->decrement('stock', $item->quantity);
                    }

                    $order->update([
                        'status' => 'paid',
                        'payment_status' => 'paid',
                        'paid_at' => now(),
                    ]);

                    Payment::updateOrCreate(
                        ['order_id' => $order->id],
                        [
                            'payment_method' => 'stripe',
                            'transaction_id' => $validated['transaction_id'],
                            'status' => 'completed',
                            'amount' => $order->total_amount,
                            'currency' => 'usd',
                        ]
                    );
                });
            } catch (\Exception $e) {
                return $this->error($e->getMessage(), 422);
            }

            // Dispatch order status update email to customer
            try {
                if ($order->shipping_email) {
                    Mail::to($order->shipping_email)->send(new OrderStatusUpdatedMail($order->fresh(['items'])));
                }
            } catch (\Exception $e) {
                Log::warning('Failed sending payment confirmation email: ' . $e->getMessage());
            }

            return $this->success([
                'order' => $order->fresh(['items']),
            ], 'Payment confirmed successfully.');
        } else {
            // Payment failed - do not mark as paid
            $order->update([
                'payment_status' => 'failed',
            ]);

            Payment::updateOrCreate(
                ['order_id' => $order->id],
                [
                    'payment_method' => 'stripe',
                    'transaction_id' => $validated['transaction_id'],
                    'status' => 'failed',
                    'amount' => $order->total_amount,
                    'currency' => 'usd',
                ]
            );

            return $this->error('Payment failed. Please retry with a valid payment method.', 400);
        }
    }
}
