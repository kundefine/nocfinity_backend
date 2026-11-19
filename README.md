# SHOP.CO — Backend RESTful API (Laravel 12/13 + MySQL + Sanctum)

The backend engine for the **SHOP.CO** e-commerce platform. Built with Laravel 12/13, MySQL 8, Laravel Sanctum token authentication, granular Role-Based Access Control (RBAC), Mailpit email notifications, and Stripe payment gateway.

Built according to [projects_requirements.md](../projects_requirements.md).

---

## 🚀 Quick Setup Guide

### 1. Requirements
- PHP 8.2+
- Composer
- MySQL 8.0+ (or Docker)

### 2. Environment Configuration
```bash
cp .env.example .env
```

Ensure your `.env` contains:
```ini
APP_NAME=SHOP.CO
APP_ENV=local
APP_KEY=base64:QGcOb0PK5b970wIriSVhLyiluRp+KkCUQvjGTnrA+8I=
APP_URL=http://127.0.0.1:8000
FRONTEND_URL=http://localhost:3000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3307
DB_DATABASE=nocfinity
DB_USERNAME=root
DB_PASSWORD=root

MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_FROM_ADDRESS="hello@shop.co"
MAIL_FROM_NAME="SHOP.CO"

# Stripe Test Mode API Keys
STRIPE_KEY=your_stripe_publishable_key_here
STRIPE_SECRET=your_stripe_secret_key_here
STRIPE_SECRET_KEY=your_stripe_secret_key_here
```

### 3. Database Migration & Seeding
```bash
# Run all migrations and seed demo data (roles, permissions, categories, 22 products, coupons, sample orders)
php artisan migrate:fresh --seed
```

### 4. Start Server
```bash
php artisan serve
# Server runs at http://127.0.0.1:8000
```

---

## 🔐 Pre-Seeded Staff & Demo Accounts

All pre-seeded accounts share the password: **`password123`**.

| Role | Email | Description |
|:---|:---|:---|
| **Super Admin** | `superadmin@shop.co` | Full permissions (`*`), protected from edit/delete. |
| **Admin** | `admin@shop.co` | Catalog, categories, orders, coupons, customer directory. |
| **Product Manager** | `products@shop.co` | Manage product catalog and categories. |
| **Order Manager** | `orders@shop.co` | Process orders, inspect customer shipping, update delivery statuses. |
| **Customer Support** | `support@shop.co` | Read-only access to customer orders and products. |
| **Customer** | `customer@shop.co` | Retail customer account with orders and saved addresses. |

---

## 🏛️ Architecture & Security

1. **Role-Based Access Control (RBAC)**:
   - Dynamic database-driven permissions (`Role`, `Permission`, `role_user`, `permission_role`).
   - All `/api/v1/admin/*` endpoints protected via `CheckPermission` middleware (`permission:<slug>`).
2. **Order Totals & Stock Security**:
   - Backend is the single source of truth; client-side submitted prices and subtotals are completely ignored.
   - Pessimistic locking (`lockForUpdate()`) inside transactions prevents overselling.
   - Stock decrements **only when paid** (`PaymentController::confirmPayment`); cancelled orders automatically restore stock.
3. **Stripe Test Mode Gateway**:
   - Creates server-side Stripe `PaymentIntent` via official Stripe PHP SDK using order amount in cents.
   - On payment failure, orders remain pending and are never marked paid.
4. **Email Notifications**:
   - Order status changes and password reset requests dispatch branded HTML emails to Mailpit (`http://localhost:8025`).

---

## 🧪 Testing

Run the automated test suite:
```bash
php artisan test
```
*Current test suite: **12 tests, 43 assertions passing (100%)** covering auth rate limiting, stock locking, IDOR protection, and RBAC.*

---

## 📖 API Documentation

Detailed RESTful endpoints, request/response payloads, and authentication headers are documented in [API_DOCUMENTATION.md](./API_DOCUMENTATION.md).
