<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class RbacMiddlewareTest extends TestCase
{
    use DatabaseTransactions;
    public function test_unauthenticated_request_is_blocked_from_admin()
    {
        $response = $this->getJson('/api/v1/admin/dashboard/stats');
        $response->assertStatus(401);
    }

    public function test_customer_cannot_access_admin_dashboard()
    {
        $customer = User::where('email', 'customer@shop.co')->first();
        Sanctum::actingAs($customer);

        $response = $this->getJson('/api/v1/admin/dashboard/stats');
        $response->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_product_manager_can_view_products_but_not_orders()
    {
        $pm = User::where('email', 'products@shop.co')->first();
        Sanctum::actingAs($pm);

        // Can view products
        $pResponse = $this->getJson('/api/v1/admin/products');
        $pResponse->assertStatus(200);

        // Cannot view orders
        $oResponse = $this->getJson('/api/v1/admin/orders');
        $oResponse->assertStatus(403);
    }

    public function test_super_admin_has_unrestricted_access()
    {
        $superAdmin = User::where('email', 'superadmin@shop.co')->first();
        Sanctum::actingAs($superAdmin);

        $this->getJson('/api/v1/admin/dashboard/stats')->assertStatus(200);
        $this->getJson('/api/v1/admin/products')->assertStatus(200);
        $this->getJson('/api/v1/admin/orders')->assertStatus(200);
        $this->getJson('/api/v1/admin/roles')->assertStatus(200);
    }
}
