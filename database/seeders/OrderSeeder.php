<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $customer = User::where('email', 'customer@shop.co')->first();
        if (!$customer) return;

        $p1 = Product::where('sku', 'TSH-001')->first() ?? Product::first();
        $p2 = Product::where('sku', 'JEA-002')->first() ?? Product::first();
        $p3 = Product::where('sku', 'SHT-003')->first() ?? Product::first();

        $sampleOrders = [
            [
                'order_number' => 'ORD-2026-000001',
                'status' => 'delivered',
                'payment_status' => 'paid',
                'subtotal' => 380.00,
                'discount_amount' => 76.00,
                'delivery_fee' => 0.00,
                'total_amount' => 304.00,
                'coupon_code' => 'SHOP20',
                'shipping_name' => 'Alex Customer',
                'shipping_email' => 'customer@shop.co',
                'shipping_phone' => '+1 555-0199',
                'shipping_address' => '742 Evergreen Terrace',
                'shipping_city' => 'Springfield',
                'shipping_postal_code' => '97477',
                'shipping_country' => 'United States',
                'paid_at' => now()->subDays(10),
                'items' => [
                    ['product' => $p1, 'quantity' => 1, 'price' => 120.00, 'size' => 'Large', 'color' => 'Brown'],
                    ['product' => $p2, 'quantity' => 1, 'price' => 260.00, 'size' => 'Medium', 'color' => 'Denim Blue'],
                ],
            ],
            [
                'order_number' => 'ORD-2026-000002',
                'status' => 'shipped',
                'payment_status' => 'paid',
                'subtotal' => 180.00,
                'discount_amount' => 0.00,
                'delivery_fee' => 15.00,
                'total_amount' => 195.00,
                'coupon_code' => null,
                'shipping_name' => 'Sarah Miller',
                'shipping_email' => 'sarah.m@example.com',
                'shipping_phone' => '+1 555-0211',
                'shipping_address' => '100 North Main St',
                'shipping_city' => 'Austin',
                'shipping_postal_code' => '78701',
                'shipping_country' => 'United States',
                'paid_at' => now()->subDays(3),
                'items' => [
                    ['product' => $p3, 'quantity' => 1, 'price' => 180.00, 'size' => 'Large', 'color' => 'Red Check'],
                ],
            ],
            [
                'order_number' => 'ORD-2026-000003',
                'status' => 'processing',
                'payment_status' => 'paid',
                'subtotal' => 240.00,
                'discount_amount' => 48.00,
                'delivery_fee' => 15.00,
                'total_amount' => 207.00,
                'coupon_code' => 'SHOP20',
                'shipping_name' => 'James Lee',
                'shipping_email' => 'james.lee@example.com',
                'shipping_phone' => '+1 555-0482',
                'shipping_address' => '450 West 33rd St',
                'shipping_city' => 'New York',
                'shipping_postal_code' => '10001',
                'shipping_country' => 'United States',
                'paid_at' => now()->subDay(),
                'items' => [
                    ['product' => $p1, 'quantity' => 2, 'price' => 120.00, 'size' => 'Medium', 'color' => 'Green'],
                ],
            ],
            [
                'order_number' => 'ORD-2026-000004',
                'status' => 'pending',
                'payment_status' => 'pending',
                'subtotal' => 120.00,
                'discount_amount' => 0.00,
                'delivery_fee' => 15.00,
                'total_amount' => 135.00,
                'coupon_code' => null,
                'shipping_name' => 'Samantha Davis',
                'shipping_email' => 'samantha@example.com',
                'shipping_phone' => '+1 555-0899',
                'shipping_address' => '880 Market St',
                'shipping_city' => 'San Francisco',
                'shipping_postal_code' => '94102',
                'shipping_country' => 'United States',
                'paid_at' => null,
                'items' => [
                    ['product' => $p1, 'quantity' => 1, 'price' => 120.00, 'size' => 'Small', 'color' => 'Brown'],
                ],
            ],
            [
                'order_number' => 'ORD-2026-000005',
                'status' => 'cancelled',
                'payment_status' => 'refunded',
                'subtotal' => 260.00,
                'discount_amount' => 0.00,
                'delivery_fee' => 0.00,
                'total_amount' => 260.00,
                'coupon_code' => null,
                'shipping_name' => 'Ethan Ross',
                'shipping_email' => 'ethan@example.com',
                'shipping_phone' => '+1 555-0912',
                'shipping_address' => '120 Ocean Drive',
                'shipping_city' => 'Miami',
                'shipping_postal_code' => '33139',
                'shipping_country' => 'United States',
                'paid_at' => now()->subDays(5),
                'items' => [
                    ['product' => $p2, 'quantity' => 1, 'price' => 260.00, 'size' => 'Large', 'color' => 'Black'],
                ],
            ],
        ];

        foreach ($sampleOrders as $sData) {
            $items = $sData['items'];
            unset($sData['items']);

            $order = Order::firstOrCreate(
                ['order_number' => $sData['order_number']],
                array_merge($sData, ['user_id' => $customer->id])
            );

            foreach ($items as $item) {
                OrderItem::firstOrCreate(
                    [
                        'order_id' => $order->id,
                        'product_id' => $item['product']->id,
                        'size' => $item['size'],
                        'color' => $item['color'],
                    ],
                    [
                        'product_name' => $item['product']->name,
                        'price' => $item['price'],
                        'quantity' => $item['quantity'],
                        'subtotal' => $item['price'] * $item['quantity'],
                    ]
                );
            }

            if ($order->payment_status === 'paid' || $order->payment_status === 'refunded') {
                Payment::firstOrCreate(
                    ['order_id' => $order->id],
                    [
                        'payment_method' => 'stripe',
                        'transaction_id' => 'ch_' . substr(md5($order->order_number), 0, 24),
                        'status' => $order->payment_status === 'paid' ? 'completed' : 'refunded',
                        'amount' => $order->total_amount,
                        'currency' => 'usd',
                        'raw_response' => ['status' => 'succeeded'],
                    ]
                );
            }
        }
    }
}
