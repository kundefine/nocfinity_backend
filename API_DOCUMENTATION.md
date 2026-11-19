# SHOP.CO E-Commerce RESTful API Documentation (v1)

This is the backend API documentation for the **SHOP.CO** e-commerce application built with **Laravel 13**, **Laravel Sanctum**, **MySQL 8.4**, and **Stripe SDK**.

---

## 1. Architecture & Security Highlights

* **Architecture Overview**:
  * **Controllers**: Clean separation between customer storefront controllers (`App\Http\Controllers\Api`) and administrative RBAC controllers (`App\Http\Controllers\Api\Admin`).
  * **Security & Source of Truth**: All prices, discount calculations, delivery fees, and order totals are computed strictly on the backend. Frontend prices and totals are never trusted.
  * **Overselling Prevention**: Stock validation and order placement utilize database transactions with pessimistic row-level locking (`Product::lockForUpdate()`) to ensure atomic stock decrement.
  * **Stock Restoration**: When an order is cancelled, stock is automatically restored to the affected products within a transaction.
  * **IDOR Protection**: Customers can only view and manage their own orders and cart items.
  * **RBAC Enforcement**: Granular permissions (e.g. `products.create`, `orders.update_status`, `reports.view`) enforced at the route level via custom middleware (`CheckPermission`). The `super-admin` role cannot be modified or deleted.

---

## 2. Authentication Credentials (Seed Data)

| Role | Email | Password | Permissions |
|---|---|---|---|
| **Super Admin** | `superadmin@shop.co` | `password123` | Full unrestricted access |
| **Admin** | `admin@shop.co` | `password123` | All permissions except role management |
| **Product Manager** | `products@shop.co` | `password123` | Catalog, inventory, categories |
| **Order Manager** | `orders@shop.co` | `password123` | Orders, status updates, coupons, customer view |
| **Customer Support**| `support@shop.co` | `password123` | Orders view, customers view, products view |
| **Customer** | `customer@shop.co` | `password123` | Storefront customer ordering |

---

## 3. Storefront API Endpoints

### Authentication
* `POST /api/v1/auth/register` &mdash; Customer registration (returns Sanctum token)
* `POST /api/v1/auth/login` &mdash; Customer login (rate limited to 5 attempts, generic error messages)
* `GET /api/v1/auth/me` &mdash; Get current authenticated user details and permissions (Bearer Token)
* `POST /api/v1/auth/logout` &mdash; Revoke token
* `PUT /api/v1/auth/profile` &mdash; Update customer profile
* `PUT /api/v1/auth/password` &mdash; Update password
* `POST /api/v1/auth/forgot-password` &mdash; Request password reset

### Catalog & Categories
* `GET /api/v1/categories` &mdash; Hierarchical category tree (Men, Women, Accessories with subcategories)
* `GET /api/v1/products` &mdash; List products with filters:
  * `q` / `search`: Text search
  * `category`: Slug or ID
  * `style` / `dress_style`: `casual`, `formal`, `party`, `gym`
  * `min_price` / `max_price`: Price range
  * `sort`: `price_asc`, `price_desc`, `rating`, `newest`, `popular`
  * `per_page`: Pagination limit (default 12)
* `GET /api/v1/products/featured` &mdash; Returns `new_arrivals` and `top_selling` product collections
* `GET /api/v1/products/{idOrSlug}` &mdash; Product details with images, category, reviews, and related products
* `POST /api/v1/products/{id}/reviews` &mdash; Submit customer review (Authenticated)

### Shopping Cart
* `GET /api/v1/cart` &mdash; Fetch active cart (Supports `Bearer Token` or `X-Guest-Token` header)
* `POST /api/v1/cart/items` &mdash; Add item (validates stock, rejects out of stock)
  * Payload: `{ "product_id": 1, "quantity": 1, "size": "Large", "color": "Brown" }`
* `PUT /api/v1/cart/items/{id}` &mdash; Update item quantity
* `DELETE /api/v1/cart/items/{id}` &mdash; Remove item
* `DELETE /api/v1/cart` &mdash; Clear cart
* `POST /api/v1/cart/merge` &mdash; Merge guest cart into user account upon login

### Coupons & Checkout
* `POST /api/v1/coupons/apply` &mdash; Validates coupon code and calculates exact discount
  * Payload: `{ "code": "SHOP20", "subtotal": 150.00 }`
  * Seed coupons: `SHOP20` (20% off), `FIRST10` ($10 off), `SUMMER50` ($50 off over $200)
* `POST /api/v1/checkout` &mdash; Secure checkout with DB row-locking and stock deduction
  * Payload:
    ```json
    {
      "items": [
        { "product_id": 1, "quantity": 1, "size": "Large", "color": "Brown" }
      ],
      "coupon_code": "SHOP20",
      "shipping_name": "Alex Customer",
      "shipping_email": "customer@shop.co",
      "shipping_phone": "+1 555-0199",
      "shipping_address": "742 Evergreen Terrace",
      "shipping_city": "Springfield",
      "shipping_postal_code": "97477",
      "shipping_country": "United States"
    }
    ```
* `GET /api/v1/my-orders` &mdash; Customer order history (Authenticated)
* `GET /api/v1/orders/{id}` &mdash; Single order details (IDOR protected)

### Payments (Stripe)
* `POST /api/v1/payments/intent` &mdash; Create Stripe PaymentIntent
* `POST /api/v1/payments/confirm` &mdash; Confirm payment and mark order as `paid`

---

## 4. Admin Panel API Endpoints (Protected by RBAC)

All admin endpoints require `auth:sanctum` and are verified by `permission:<slug>` middleware.

* `POST /api/v1/admin/auth/login` &mdash; Staff/Admin authentication

### Dashboard & Analytics
* `GET /api/v1/admin/dashboard/stats` &mdash; (`permission:reports.view`)
  * Today's orders count
  * Today's revenue
  * Total customers count
  * Low-stock alert count (< 5 items)
  * 30-day sales chart data
  * Recent orders list
* `GET /api/v1/admin/dashboard/customers` &mdash; (`permission:customers.view`)
  * Customers list with total orders, total spend, and order history

### Products & Inventory
* `GET /api/v1/admin/products` &mdash; (`permission:products.view`)
* `POST /api/v1/admin/products` &mdash; (`permission:products.create`)
* `PUT /api/v1/admin/products/{id}` &mdash; (`permission:products.edit`)
* `DELETE /api/v1/admin/products/{id}` &mdash; (`permission:products.delete`)

### Categories Management
* `GET /api/v1/admin/categories` &mdash; (`permission:categories.manage`)
* `POST /api/v1/admin/categories` &mdash; (`permission:categories.manage`)
* `PUT /api/v1/admin/categories/{id}` &mdash; (`permission:categories.manage`)
* `DELETE /api/v1/admin/categories/{id}` &mdash; (`permission:categories.manage`)

### Orders Management
* `GET /api/v1/admin/orders` &mdash; (`permission:orders.view`)
* `GET /api/v1/admin/orders/{id}` &mdash; (`permission:orders.view`)
* `PUT /api/v1/admin/orders/{id}/status` &mdash; (`permission:orders.update_status`)
  * Status updates (`pending` → `paid` → `processing` → `shipped` → `delivered`, `cancelled`). Cancelling restores product stock.

### Coupons Management
* `GET /api/v1/admin/coupons` &mdash; (`permission:coupons.manage`)
* `POST /api/v1/admin/coupons` &mdash; (`permission:coupons.manage`)
* `PUT /api/v1/admin/coupons/{id}` &mdash; (`permission:coupons.manage`)
* `DELETE /api/v1/admin/coupons/{id}` &mdash; (`permission:coupons.manage`)

### Roles & Permissions Matrix
* `GET /api/v1/admin/roles` &mdash; (`permission:roles.manage`)
* `POST /api/v1/admin/roles` &mdash; (`permission:roles.manage`)
* `PUT /api/v1/admin/roles/{id}` &mdash; (`permission:roles.manage`)
* `DELETE /api/v1/admin/roles/{id}` &mdash; (`permission:roles.manage`)
* `GET /api/v1/admin/permissions` &mdash; (`permission:roles.manage`)
* `GET /api/v1/admin/staff` &mdash; (`permission:roles.manage`)
* `POST /api/v1/admin/staff` &mdash; (`permission:roles.manage`)
* `PUT /api/v1/admin/staff/{id}` &mdash; (`permission:roles.manage`)
* `DELETE /api/v1/admin/staff/{id}` &mdash; (`permission:roles.manage`)
