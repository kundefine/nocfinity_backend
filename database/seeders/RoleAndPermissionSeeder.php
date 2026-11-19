<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Granular Permissions
        $permissions = [
            // Products
            ['name' => 'View Products', 'slug' => 'products.view', 'group' => 'products'],
            ['name' => 'Create Products', 'slug' => 'products.create', 'group' => 'products'],
            ['name' => 'Edit Products', 'slug' => 'products.edit', 'group' => 'products'],
            ['name' => 'Delete Products', 'slug' => 'products.delete', 'group' => 'products'],

            // Categories
            ['name' => 'Manage Categories', 'slug' => 'categories.manage', 'group' => 'categories'],

            // Orders
            ['name' => 'View Orders', 'slug' => 'orders.view', 'group' => 'orders'],
            ['name' => 'Update Order Status', 'slug' => 'orders.update_status', 'group' => 'orders'],

            // Coupons
            ['name' => 'Manage Coupons', 'slug' => 'coupons.manage', 'group' => 'coupons'],

            // Roles & Staff
            ['name' => 'Manage Roles & Staff', 'slug' => 'roles.manage', 'group' => 'roles'],

            // Customers
            ['name' => 'View Customers', 'slug' => 'customers.view', 'group' => 'customers'],

            // Reports / Dashboard
            ['name' => 'View Reports & Analytics', 'slug' => 'reports.view', 'group' => 'reports'],
        ];

        $createdPermissions = [];
        foreach ($permissions as $p) {
            $createdPermissions[$p['slug']] = Permission::firstOrCreate(['slug' => $p['slug']], $p);
        }

        // 2. Create Roles (minimum 4: Super Admin, Admin, Product Manager, Order Manager, Support)
        $superAdminRole = Role::firstOrCreate(
            ['slug' => 'super-admin'],
            [
                'name' => 'Super Admin',
                'description' => 'Unrestricted access to all system settings and entities.',
                'is_system' => true,
            ]
        );
        $superAdminRole->permissions()->sync(Permission::all());

        $adminRole = Role::firstOrCreate(
            ['slug' => 'admin'],
            [
                'name' => 'Admin',
                'description' => 'Full administrative access except modifying Super Admin roles.',
                'is_system' => false,
            ]
        );
        $adminRole->permissions()->sync(
            Permission::where('slug', '!=', 'roles.manage')->get()
        );

        $productManagerRole = Role::firstOrCreate(
            ['slug' => 'product-manager'],
            [
                'name' => 'Product Manager',
                'description' => 'Manage catalog, inventory, and categories.',
                'is_system' => false,
            ]
        );
        $productManagerRole->permissions()->sync(
            Permission::whereIn('group', ['products', 'categories'])->get()
        );

        $orderManagerRole = Role::firstOrCreate(
            ['slug' => 'order-manager'],
            [
                'name' => 'Order Manager',
                'description' => 'Process orders, manage fulfillment, and configure discounts.',
                'is_system' => false,
            ]
        );
        $orderManagerRole->permissions()->sync(
            Permission::whereIn('slug', ['orders.view', 'orders.update_status', 'coupons.manage', 'customers.view'])->get()
        );

        $supportRole = Role::firstOrCreate(
            ['slug' => 'support'],
            [
                'name' => 'Customer Support',
                'description' => 'Review customer details, orders, and product availability.',
                'is_system' => false,
            ]
        );
        $supportRole->permissions()->sync(
            Permission::whereIn('slug', ['orders.view', 'customers.view', 'products.view'])->get()
        );

        // 3. Create Seed Staff and Admin Accounts
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@shop.co'],
            [
                'name' => 'Super Administrator',
                'password' => Hash::make('password123'),
                'phone' => '+1234567890',
                'is_active' => true,
            ]
        );
        $superAdmin->roles()->sync([$superAdminRole->id]);

        $admin = User::firstOrCreate(
            ['email' => 'admin@shop.co'],
            [
                'name' => 'Store Administrator',
                'password' => Hash::make('password123'),
                'phone' => '+1234567891',
                'is_active' => true,
            ]
        );
        $admin->roles()->sync([$adminRole->id]);

        $pm = User::firstOrCreate(
            ['email' => 'products@shop.co'],
            [
                'name' => 'Product Lead',
                'password' => Hash::make('password123'),
                'phone' => '+1234567892',
                'is_active' => true,
            ]
        );
        $pm->roles()->sync([$productManagerRole->id]);

        $om = User::firstOrCreate(
            ['email' => 'orders@shop.co'],
            [
                'name' => 'Fulfillment Lead',
                'password' => Hash::make('password123'),
                'phone' => '+1234567893',
                'is_active' => true,
            ]
        );
        $om->roles()->sync([$orderManagerRole->id]);

        // Customer Demo User
        $customerUser = User::firstOrCreate(
            ['email' => 'customer@shop.co'],
            [
                'name' => 'Alex Customer',
                'password' => Hash::make('password123'),
                'phone' => '+1234567894',
                'is_active' => true,
            ]
        );

        // Seed default Home and Office addresses
        $customerUser->update([
            'addresses' => [
                [
                    'id' => 'addr-home',
                    'type' => 'home',
                    'label' => 'Home',
                    'address' => '742 Evergreen Terrace',
                    'city' => 'Springfield',
                    'postal_code' => '97477',
                    'country' => 'United States',
                    'is_default' => true,
                ],
                [
                    'id' => 'addr-office',
                    'type' => 'office',
                    'label' => 'Office',
                    'address' => '100 Enterprise Way, Suite 400',
                    'city' => 'Silicon Valley',
                    'postal_code' => '94025',
                    'country' => 'United States',
                    'is_default' => false,
                ],
            ]
        ]);
    }
}
