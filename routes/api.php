<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CouponController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\Admin\RoleController;
use App\Http\Controllers\Api\Admin\StaffController;
use App\Http\Controllers\Api\Admin\DashboardController;

Route::prefix('v1')->group(function () {

    // ==========================================
    // 1. PUBLIC AUTH & STOREFRONT ENDPOINTS
    // ==========================================

    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    });

    // Admin Auth
    Route::post('/admin/auth/login', [AuthController::class, 'adminLogin']);

    // Categories
    Route::get('/categories', [CategoryController::class, 'index']);

    // Products
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/featured', [ProductController::class, 'featured']);
    Route::get('/products/{idOrSlug}', [ProductController::class, 'show']);

    // Cart (works for both guests via X-Guest-Token header and logged-in users)
    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'index']);
        Route::post('/items', [CartController::class, 'addItem']);
        Route::put('/items/{id}', [CartController::class, 'updateItem']);
        Route::delete('/items/{id}', [CartController::class, 'removeItem']);
        Route::delete('/', [CartController::class, 'clear']);
    });

    // Coupons
    Route::post('/coupons/apply', [CouponController::class, 'apply']);

    // Checkout (creates order, decrements stock, supports guest or logged-in)
    Route::post('/checkout', [OrderController::class, 'checkout']);
    Route::get('/orders/{id}', [OrderController::class, 'show']); // Can fetch by order_number for guests

    // Payments (Stripe)
    Route::post('/payments/intent', [PaymentController::class, 'createPaymentIntent']);
    Route::post('/payments/confirm', [PaymentController::class, 'confirmPayment']);

    // ==========================================
    // 2. AUTHENTICATED CUSTOMER ENDPOINTS
    // ==========================================

    Route::middleware('auth:sanctum')->group(function () {
        // Customer profile
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::put('/auth/profile', [AuthController::class, 'updateProfile']);
        Route::put('/auth/password', [AuthController::class, 'updatePassword']);
        Route::put('/auth/addresses', [AuthController::class, 'updateAddresses']);

        // Merge guest cart
        Route::post('/cart/merge', [CartController::class, 'merge']);

        // Order history
        Route::get('/my-orders', [OrderController::class, 'myOrders']);

        // Product reviews
        Route::post('/products/{id}/reviews', [ProductController::class, 'addReview']);
    });

    // ==========================================
    // 3. ADMIN PANEL ENDPOINTS (RBAC PROTECTED)
    // ==========================================

    Route::prefix('admin')->middleware(['auth:sanctum'])->group(function () {
        
        // Admin Auth & Profile
        Route::get('/auth/me', [AuthController::class, 'adminMe']);
        Route::post('/auth/logout', [AuthController::class, 'adminLogout']);
        Route::put('/auth/profile', [AuthController::class, 'updateProfile']);
        Route::put('/auth/password', [AuthController::class, 'updatePassword']);

        // Dashboard & Analytics
        Route::get('/dashboard/stats', [DashboardController::class, 'stats'])
            ->middleware('permission:reports.view');
        Route::get('/dashboard/customers', [DashboardController::class, 'customers'])
            ->middleware('permission:customers.view');

        // Products Management
        Route::get('/products', [ProductController::class, 'adminIndex'])
            ->middleware('permission:products.view');
        Route::post('/products', [ProductController::class, 'store'])
            ->middleware('permission:products.create');
        Route::put('/products/{id}', [ProductController::class, 'update'])
            ->middleware('permission:products.edit');
        Route::delete('/products/{id}', [ProductController::class, 'destroy'])
            ->middleware('permission:products.delete');

        // Categories Management
        Route::get('/categories', [CategoryController::class, 'adminIndex'])
            ->middleware('permission:categories.manage');
        Route::post('/categories', [CategoryController::class, 'store'])
            ->middleware('permission:categories.manage');
        Route::put('/categories/{id}', [CategoryController::class, 'update'])
            ->middleware('permission:categories.manage');
        Route::delete('/categories/{id}', [CategoryController::class, 'destroy'])
            ->middleware('permission:categories.manage');

        // Orders Management
        Route::get('/orders', [OrderController::class, 'adminIndex'])
            ->middleware('permission:orders.view');
        Route::get('/orders/{id}', [OrderController::class, 'adminShow'])
            ->middleware('permission:orders.view');
        Route::put('/orders/{id}/status', [OrderController::class, 'updateStatus'])
            ->middleware('permission:orders.update_status');

        // Coupons Management
        Route::get('/coupons', [CouponController::class, 'index'])
            ->middleware('permission:coupons.manage');
        Route::post('/coupons', [CouponController::class, 'store'])
            ->middleware('permission:coupons.manage');
        Route::put('/coupons/{id}', [CouponController::class, 'update'])
            ->middleware('permission:coupons.manage');
        Route::delete('/coupons/{id}', [CouponController::class, 'destroy'])
            ->middleware('permission:coupons.manage');

        // Roles & Permissions Management
        Route::get('/roles', [RoleController::class, 'index'])
            ->middleware('permission:roles.manage');
        Route::post('/roles', [RoleController::class, 'store'])
            ->middleware('permission:roles.manage');
        Route::put('/roles/{id}', [RoleController::class, 'update'])
            ->middleware('permission:roles.manage');
        Route::delete('/roles/{id}', [RoleController::class, 'destroy'])
            ->middleware('permission:roles.manage');
        Route::get('/permissions', [RoleController::class, 'permissions'])
            ->middleware('permission:roles.manage');

        // Staff Management
        Route::get('/staff', [StaffController::class, 'index'])
            ->middleware('permission:roles.manage');
        Route::post('/staff', [StaffController::class, 'store'])
            ->middleware('permission:roles.manage');
        Route::put('/staff/{id}', [StaffController::class, 'update'])
            ->middleware('permission:roles.manage');
        Route::delete('/staff/{id}', [StaffController::class, 'destroy'])
            ->middleware('permission:roles.manage');
    });

});
