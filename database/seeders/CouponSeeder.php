<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Coupon;

class CouponSeeder extends Seeder
{
    public function run(): void
    {
        $coupons = [
            [
                'code' => 'SHOP20',
                'type' => 'percentage',
                'value' => 20.00,
                'min_order_amount' => 0.00,
                'usage_limit' => 500,
                'used_count' => 14,
                'is_active' => true,
                'starts_at' => now()->subMonth(),
                'expires_at' => now()->addYear(),
            ],
            [
                'code' => 'FIRST10',
                'type' => 'fixed',
                'value' => 10.00,
                'min_order_amount' => 50.00,
                'usage_limit' => 200,
                'used_count' => 8,
                'is_active' => true,
                'starts_at' => now()->subMonth(),
                'expires_at' => now()->addYear(),
            ],
            [
                'code' => 'SUMMER50',
                'type' => 'fixed',
                'value' => 50.00,
                'min_order_amount' => 200.00,
                'usage_limit' => 50,
                'used_count' => 3,
                'is_active' => true,
                'starts_at' => now()->subMonth(),
                'expires_at' => now()->addMonths(3),
            ],
            [
                'code' => 'EXPIRED30',
                'type' => 'percentage',
                'value' => 30.00,
                'min_order_amount' => 0.00,
                'usage_limit' => 10,
                'used_count' => 10,
                'is_active' => false,
                'starts_at' => now()->subMonths(3),
                'expires_at' => now()->subMonth(),
            ],
        ];

        foreach ($coupons as $c) {
            Coupon::firstOrCreate(['code' => $c['code']], $c);
        }
    }
}
