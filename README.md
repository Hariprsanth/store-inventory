# Store Order & Inventory Mini-System

A Laravel application for managing products, customers, retail orders, stock levels, tax calculations, and order-confirmation email delivery.

## Tech Stack

- **Framework:** Laravel 11 (PHP 8.2)
- **Database:** MySQL (SQLite supported for quick local setup / testing)
- **ORM:** Eloquent, with `lockForUpdate()` row locking and DB transactions for concurrency-safe stock updates
- **Queue:** Database queue driver (`QUEUE_CONNECTION=database`) for the order-confirmation job
- **Mail:** Laravel Mailable + Blade HTML view, switchable between the `log` driver and Gmail SMTP via `.env`
- **Validation:** Form Request classes (`StoreOrderRequest`)
- **API layer:** JSON API Resources (`OrderResource`) for consistent response shape
- **Testing:** PHPUnit via `php artisan test`, using `RefreshDatabase`, `Queue::fake()`, and concurrent-request simulation

## Requirements

- PHP 8.2 or newer
- Composer
- MySQL
- A Gmail account with a Google App Password if real email delivery is required

## Installation

From the project directory:

```bash
composer install
copy .env.example .env
php artisan migrate --seed
```

Configure the database in `.env`. The example file uses SQLite. A MySQL configuration looks like this:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=store_inventory
DB_USERNAME=root
DB_PASSWORD=
```

After changing `.env`, clear Laravel's cached configuration:

```bash
php artisan config:clear
```

## Running The Application

Open two terminals in the project directory.

Terminal 1, start the web server:

```bash
php artisan serve
```

Terminal 2, start the queue worker:

```bash
php artisan queue:work --sleep=1 --tries=1
```

Open `http://127.0.0.1:8000` in a browser.

Available UI pages:

- `/products` - view and manage products
- `/products/low-stock` - view products below a stock threshold
- `/orders/create` - create an order
- `/orders/history` - view customer order history

The queue worker must remain running for queued order-confirmation emails to be processed.

## How The Order Flow Works

1. The order form loads products from `GET /api/products`.
2. The user enters a customer name, customer email, products, and quantities.
3. The form sends the order to `POST /api/orders`.
4. `StoreOrderRequest` validates the customer and item data.
5. `OrderService` runs the stock check, total calculation, order creation, and stock deduction inside one database transaction.
6. Each order line stores the product, quantity, unit price, tax percentage, line subtotal, line tax, and line total.
7. Stock is deducted only when enough stock is available, using an atomic `where('stock', '>=', $quantity)` update combined with a row-level `lockForUpdate()` lock.
8. `SendOrderConfirmation` is dispatched to the database queue after the order is created.
9. The queue worker runs the job, which builds an `OrderConfirmedMail` (an HTML Mailable with a dedicated Blade view) and sends it to the customer's email address.
10. The job also writes an order-confirmation entry to `storage/logs/laravel.log`, independent of whether the mail transport is `log` or `smtp`.

## Email Configuration

The brief permits either a log entry or a fake mailer — real SMTP delivery is optional and was added as an extra verification step.

**Log driver (no SMTP needed):**

```env
MAIL_MAILER=log
MAIL_FROM_ADDRESS=no-reply@store-inventory.test
MAIL_FROM_NAME="Store Inventory (No Reply)"
```

The generated email is written in full (subject, headers, HTML body) to `storage/logs/laravel.log` — nothing is sent over the network.

**Real Gmail SMTP (optional):**

Enable 2-Step Verification on the Google account, then create a Google App Password. Do not use the normal Gmail account password — Gmail rejects it for SMTP.

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-address@gmail.com
MAIL_PASSWORD=your-16-character-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-address@gmail.com
MAIL_FROM_NAME="Store Inventory (No Reply)"
```

Gmail always sends `From` as the authenticated account address; the display name is used to signal "no-reply" instead of an unreachable address, since Gmail does not allow sending from an arbitrary `From` address without a verified custom domain.

After changing the mailer configuration:

```bash
php artisan config:clear
php artisan queue:work --sleep=1 --tries=1
```

## API Endpoints

### Create an order

`POST /api/orders`

```json
{
  "customer_name": "Jane Doe",
  "customer_email": "customer@example.com",
  "items": [
    {
      "product_id": 1,
      "quantity": 2
    }
  ]
}
```

A successful request returns HTTP `201` and the created order. If stock is unavailable, it returns HTTP `422` and the transaction is rolled back.

### Customer order history

`GET /api/orders/history?email=customer@example.com`

### List products

`GET /api/products`

### Low-stock products

`GET /api/products/low-stock?threshold=10`

The endpoint returns products where stock is strictly less than the supplied threshold. The default threshold is `10`.

### Product management

- `POST /api/products`
- `PUT /api/products/{product}`
- `DELETE /api/products/{product}`

## Stock Concurrency

The order service locks the product row with `lockForUpdate()` and performs an atomic update with `where stock >= quantity`. The operation runs inside a database transaction. When two requests compete for the final unit, one succeeds and the other receives HTTP `422`; stock cannot become negative or be oversold on a transactional database.

## Database Design

The normalized schema contains:

- `products`: catalog details and current stock
- `customers`: unique customer email and name
- `orders`: customer reference and calculated totals
- `order_lines`: products and price/tax snapshots for each order

Order lines preserve the price and tax values at the time of purchase, so old orders remain accurate after catalog changes.

## Seed Data

Run:

```bash
php artisan db:seed
```

The seeders create sample customers, products, and a low-stock sample product. Factories are available for customers and products.

## Testing

### Run the automated test suite

```bash
php artisan test
```

Run a single test file:

```bash
php artisan test tests/Feature/OrderTest.php
```

Run a single test method:

```bash
php artisan test --filter=test_only_one_order_can_consume_the_last_unit
```

Tests use an isolated in-memory/test database via `RefreshDatabase`, so the development database is never touched.

The feature tests cover:

- order creation, total calculation, and correct grand-total response
- stock deduction on successful order creation
- queued confirmation dispatch (`Queue::fake()` + `Queue::assertPushed(SendOrderConfirmation::class)`)
- insufficient-stock rejection (HTTP `422`), with stock and order count left unchanged
- protection against two requests consuming the final unit of stock at the same time

### Manually test the order-confirmation email

1. Set `MAIL_MAILER=log` in `.env` (no SMTP required), then `php artisan config:clear`.
2. Start the app and worker in two terminals:
   ```bash
   php artisan serve
   php artisan queue:work --sleep=1 --tries=1
   ```
3. Submit an order from `/orders/create`, or `POST /api/orders` directly with a tool like Postman.
4. Watch the queue worker terminal — it should log that `SendOrderConfirmation` was processed.
5. Open `storage/logs/laravel.log` and confirm an order-confirmation log entry, plus the full rendered HTML email, appear for that order.

### Manually test stock concurrency

Send the same `POST /api/orders` payload twice in quick succession for a product with `stock = 1`. The first request should return `201`, the second `422`, and the product's stock should end at `0` — never negative.

### Manually test low-stock and history endpoints

```bash
curl "http://127.0.0.1:8000/api/products/low-stock?threshold=10"
curl "http://127.0.0.1:8000/api/orders/history?email=customer@example.com"
```