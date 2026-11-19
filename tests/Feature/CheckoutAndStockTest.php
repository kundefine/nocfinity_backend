<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Order;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class CheckoutAndStockTest extends TestCase
{
    use DatabaseTransactions;
    public function test_checkout_creates_order_and_decrements_stock_on_payment()
    {
        $customer = User::where('email', 'customer@shop.co')->first();
        Sanctum::actingAs($customer);

        $product = Product::where('stock', '>', 5)->first();
        $initialStock = $product->stock;
        $orderQty = 2;

        $response = $this->postJson('/api/v1/checkout', [
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => $orderQty,
                    'size' => 'Large',
                    'color' => 'Default',
                ]
            ],
            'coupon_code' => 'SHOP20',
            'shipping_name' => 'Test Customer',
            'shipping_email' => 'test@example.com',
            'shipping_address' => '123 Test Ave',
            'shipping_city' => 'Testville',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.order.status', 'pending');

        $orderId = $response->json('data.order.id');
        $this->assertNotNull($orderId);

        // Stock remains intact while order is pending
        $product->refresh();
        $this->assertEquals($initialStock, $product->stock);

        // Verify order items created
        $this->assertDatabaseHas('order_items', [
            'order_id' => $orderId,
            'product_id' => $product->id,
            'quantity' => $orderQty,
        ]);

        // Requirement: Stock is decremented only when an order is paid
        $payResponse = $this->postJson('/api/v1/payments/confirm', [
            'order_id' => $orderId,
            'transaction_id' => 'pi_test_123456789',
            'status' => 'succeeded',
        ]);

        $payResponse->assertStatus(200);

        // Verify stock is now decremented
        $product->refresh();
        $this->assertEquals($initialStock - $orderQty, $product->stock);

        // Requirement: Cancelled orders restore stock
        $superadmin = User::where('email', 'superadmin@shop.co')->first();
        Sanctum::actingAs($superadmin);

        $cancelResponse = $this->putJson("/api/v1/admin/orders/{$orderId}/status", [
            'status' => 'cancelled',
        ]);
        $cancelResponse->assertStatus(200);

        // Verify stock is restored
        $product->refresh();
        $this->assertEquals($initialStock, $product->stock);
    }

    public function test_checkout_rejects_insufficient_stock()
    {
        $customer = User::where('email', 'customer@shop.co')->first();
        Sanctum::actingAs($customer);

        $product = Product::where('stock', 0)->first(); // Cargo Utility Pants is 0
        if (!$product) {
            $product = Product::first();
            $product->update(['stock' => 0]);
        }

        $response = $this->postJson('/api/v1/checkout', [
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                ]
            ],
            'shipping_name' => 'Test Customer',
            'shipping_email' => 'test@example.com',
            'shipping_address' => '123 Test Ave',
            'shipping_city' => 'Testville',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_customer_cannot_view_another_customers_order_idor_protection()
    {
        $customerA = User::where('email', 'customer@shop.co')->first();
        $customerB = User::factory()->create();

        $orderA = Order::where('user_id', $customerA->id)->first();
        $this->assertNotNull($orderA);

        // Customer B tries to view Customer A's order
        Sanctum::actingAs($customerB);
        $response = $this->getJson("/api/v1/orders/{$orderA->id}");

        $response->assertStatus(403)
            ->assertJsonPath('success', false);
    }
}
