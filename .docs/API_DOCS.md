# Backend API - Documentation (POS System)

Built with **Laravel 13** + **PostgreSQL**.
Authentication uses **Sanctum** with **device tokens**.

---

## 1. Overview

Current architecture:

- `products` is the global catalog.
- `branches` stores stores/sucursales.
- `devices` belongs to exactly one branch and authenticates with `identifier + secret`.
- `branch_product` stores the operational product data per branch:
  - `price`
  - `stock_quantity`
  - `min_stock`
  - `is_available`

Operational endpoints derive branch context from the authenticated device.
The frontend should not send `branch_id` for sales, carts, inventory movements, or cash-session open/current flows.

---

## 2. Standard Response Format

**Success - list / show (200):**
```json
{
  "success": true,
  "data": {}
}
```

**Success - create / update (200 / 201):**
```json
{
  "success": true,
  "message": "Operation completed successfully.",
  "data": {}
}
```

**Success - delete (200):**
```json
{
  "success": true,
  "message": "Record deleted successfully."
}
```

**Validation error (422):**
```json
{
  "success": false,
  "message": "Validation failed.",
  "errors": {
    "field": ["The field is required."]
  }
}
```

**Auth error (401):**
```json
{
  "message": "Unauthenticated."
}
```

**Not found (404):**
```json
{
  "success": false,
  "message": "Record not found."
}
```

**Paginated list shape:**
```json
{
  "success": true,
  "data": {
    "data": [],
    "links": {
      "first": "...",
      "last": "...",
      "prev": null,
      "next": "..."
    },
    "meta": {
      "current_page": 1,
      "last_page": 3,
      "per_page": 20,
      "total": 45
    }
  }
}
```

---

## 3. Authentication

All operational routes require `Authorization: Bearer <token>`.

### Device Auth - `DeviceAuthController`

| Method | Path | Description |
|--------|------|-------------|
| POST | `/auth/device/login` | Authenticate a device and issue token |
| POST | `/auth/device/logout` | Revoke current token |
| GET | `/auth/device/me` | Get current authenticated device |

**POST `/auth/device/login` body:**
```json
{
  "identifier": "POS-01",
  "secret": "secret-123"
}
```

**Success response:**
```json
{
  "success": true,
  "message": "Device authenticated successfully.",
  "data": {
    "token": "plain-text-token",
    "device": {
      "id": 1,
      "branch_id": 1,
      "name": "Caja 1",
      "identifier": "POS-01",
      "branch": {
        "id": 1,
        "name": "Ferreteria Metropolis"
      }
    }
  }
}
```

Rules:

- `identifier` and `secret` are required.
- inactive devices are rejected.
- inactive branches are rejected through the device login flow.

**Development seeded devices:**

| Identifier | Secret | Branch |
|---|---|---|
| `POS-01` | `secret-123` | `Ferreteria Metropolis` |
| `POS-02` | `secret-456` | `Ferreteria Metropolis` |
| `ALMACEN-01` | `secret-789` | `Ferreteria Metropolis` |

---

## 4. Endpoints

All routes are prefixed with `/api`.

### Dashboard - `DashboardController`

| Method | Path | Description |
|--------|------|-------------|
| GET | `/dashboard` | Summary for the authenticated device branch |

**Response `data`:**
```json
{
  "today": {
    "sales_count": 1,
    "revenue": 100,
    "items_sold": 2
  },
  "cash_session": {
    "id": 3,
    "opened_at": "2026-05-06 08:00:00",
    "opening_balance": 500
  },
  "inventory": {
    "total_products": 10,
    "active_products": 8,
    "low_stock_count": 2
  }
}
```

Rules:

- sales metrics are scoped to the authenticated device branch.
- `cash_session` is the current open session for the authenticated device.
- inventory metrics come from `branch_product`, not global `products`.

---

### Brands - `BrandController`

| Method | Path |
|--------|------|
| GET | `/brands` |
| POST | `/brands` |
| GET | `/brands/{id}` |
| PUT/PATCH | `/brands/{id}` |
| DELETE | `/brands/{id}` |

### Branches - `BranchController`

| Method | Path |
|--------|------|
| GET | `/branches` |
| POST | `/branches` |
| GET | `/branches/{id}` |
| PUT/PATCH | `/branches/{id}` |

### Categories - `CategoryController`

| Method | Path |
|--------|------|
| GET | `/categories` |
| POST | `/categories` |
| GET | `/categories/{id}` |
| PUT/PATCH | `/categories/{id}` |
| DELETE | `/categories/{id}` |

---

### Products - `ProductController`

| Method | Path | Description |
|--------|------|-------------|
| GET | `/products` | List products available in authenticated branch |
| GET | `/products/low-stock` | Branch products where `stock_quantity <= min_stock` |
| POST | `/products` | Create global product or attach existing SKU to current branch |
| GET | `/products/{id}` | Show product in current branch context |
| PUT/PATCH | `/products/{id}` | Update global product data |
| PATCH | `/products/{id}/branch` | Update branch-specific operational data |
| DELETE | `/products/{id}` | Remove product from current branch; deletes global product if orphaned |

**GET `/products` query filters:**

| Param | Type | Description |
|-------|------|-------------|
| `search` | string | Search across `name` and `sku` |
| `category_id` | integer | Filter by category |
| `brand_id` | integer | Filter by brand |
| `low_stock` | 1 | Only branch products where `stock_quantity <= min_stock` |

**POST `/products` body:**
```json
{
  "name": "Coca-Cola 500ml",
  "description": "Optional",
  "cost_price": 8.5,
  "sku": "COC-500",
  "unit_measure": "PZA",
  "category_id": 1,
  "brand_id": 2,
  "price": 15,
  "stock_quantity": 100,
  "min_stock": 10,
  "is_available": true
}
```

**PATCH `/products/{id}` body:**
```json
{
  "name": "Coca-Cola 600ml",
  "description": "Updated",
  "cost_price": 9,
  "sku": "COC-600",
  "unit_measure": "PZA",
  "category_id": 1,
  "brand_id": 2
}
```

**PATCH `/products/{id}/branch` body:**
```json
{
  "price": 16,
  "stock_quantity": 80,
  "min_stock": 8,
  "is_available": true
}
```

Rules:

- `sku` is required and unique globally.
- `cost_price` is global.
- `price`, `stock_quantity`, `min_stock`, and `is_available` are branch-specific.
- `POST /products` reuses an existing global product when the `sku` already exists.
- if the current branch already has that product, the API returns `422`.

**Product response shape:**
```json
{
  "id": 20,
  "name": "Apagador sencillo",
  "description": null,
  "sku": "EL-003",
  "cost_price": 28,
  "unit_measure": "PZA",
  "category_id": 4,
  "brand_id": null,
  "category": {},
  "brand": null,
  "branch_product": {
    "branch_id": 1,
    "product_id": 20,
    "price": 48,
    "stock_quantity": 38,
    "min_stock": 15,
    "is_available": true,
    "branch": {}
  }
}
```

---

### Customers - `CustomerController`

| Method | Path |
|--------|------|
| GET | `/customers` |
| POST | `/customers` |
| GET | `/customers/{id}` |
| PUT/PATCH | `/customers/{id}` |
| DELETE | `/customers/{id}` |

### Suppliers - `SupplierController`

| Method | Path |
|--------|------|
| GET | `/suppliers` |
| POST | `/suppliers` |
| GET | `/suppliers/{id}` |
| PUT/PATCH | `/suppliers/{id}` |
| DELETE | `/suppliers/{id}` |

---

### Sales - `SaleController`

| Method | Path | Description |
|--------|------|-------------|
| GET | `/sales` | List sales for authenticated branch |
| POST | `/sales` | Process sale in authenticated branch/device context |
| GET | `/sales/{id}` | Show one sale from authenticated branch |

**GET `/sales` query filters:**

| Param | Type |
|-------|------|
| `search` | string |
| `date_from` | `Y-m-d` |
| `date_to` | `Y-m-d` |
| `cash_session_id` | integer |
| `customer_id` | integer |
| `payment_method` | string |
| `status` | string |

**POST `/sales` body:**
```json
{
  "customer_id": 1,
  "cash_session_id": 3,
  "payment_method": "cash",
  "discount_amount": 5,
  "items": [
    {
      "product_id": 7,
      "quantity": 2,
      "unit_price": 25,
      "tax_amount": 4,
      "subtotal": 50,
      "total": 54
    }
  ]
}
```

Rules:

- branch is derived from the authenticated device.
- `cash_session_id` must be open.
- `cash_session_id` must belong to the authenticated device.
- stock is validated against `branch_product.stock_quantity`.
- successful sales decrement branch stock and create `inventory_movements` with `source = sale`.
- cash sales create `cash_movements` automatically.
- `sale_details.unit_price` remains the historical snapshot.

---

### Saved Carts - `SavedCartController`

| Method | Path |
|--------|------|
| GET | `/saved-carts` |
| POST | `/saved-carts` |
| GET | `/saved-carts/{id}` |
| PATCH | `/saved-carts/{id}/recover` |
| PUT/PATCH | `/saved-carts/{id}` |
| DELETE | `/saved-carts/{id}` |

**POST / PUT body:**
```json
{
  "name": "Cliente mostrador 1",
  "customer_id": 1,
  "cash_session_id": 3,
  "discount_amount": 5,
  "status": "saved",
  "notes": "Regresa en unos minutos",
  "items": [
    {
      "product_id": 7,
      "quantity": 2,
      "unit_price": 25,
      "tax_amount": 4,
      "subtotal": 50,
      "total": 54
    }
  ]
}
```

Rules:

- branch is derived from the authenticated device.
- carts never decrement stock.
- `cash_session_id` is optional.
- if present, `cash_session_id` must belong to the authenticated device.

---

### Sale Details - `SaleDetailController`

| Method | Path |
|--------|------|
| GET | `/sale-details` |
| GET | `/sale-details/{id}` |

Rules:

- results are limited to the authenticated branch.
- optional filter: `sale_id`.

---

### Cash Sessions - `CashSessionController`

| Method | Path | Description |
|--------|------|-------------|
| GET | `/cash-sessions` | List sessions for authenticated branch |
| GET | `/cash-sessions/current` | Current open session for authenticated device |
| POST | `/cash-sessions/open` | Open session for authenticated device |
| GET | `/cash-sessions/{id}` | Show one session |
| POST | `/cash-sessions/{id}/close` | Close one session |
| GET | `/cash-sessions/{id}/movements` | List session cash movements |
| POST | `/cash-sessions/{id}/movements` | Record manual cash movement |

**POST `/cash-sessions/open` body:**
```json
{
  "opening_balance": 500,
  "notes": "Morning shift"
}
```

**POST `/cash-sessions/{id}/close` body:**
```json
{
  "closing_balance": 1350,
  "notes": "End of day"
}
```

Rules:

- only one open session per device.
- branch comes from the device.
- `GET /cash-sessions/current` requires auth and returns `404` when no open session exists.
- session endpoints reject access to sessions owned by another device.

---

### Cash Movements - `CashMovementController`

| Method | Path |
|--------|------|
| GET | `/cash-sessions/{cashSession}/movements` |
| POST | `/cash-sessions/{cashSession}/movements` |

**POST body:**
```json
{
  "type": "out",
  "category": "expense",
  "amount": 75,
  "notes": "Pago de taxi"
}
```

Rules:

- session must belong to the authenticated device.
- session must be open.
- manual requests may not use `category = sale`.
- `adjustment` requires `notes`.

---

### Inventory Movements - `InventoryMovementController`

| Method | Path |
|--------|------|
| GET | `/inventory/movements` |
| POST | `/inventory/movements` |
| GET | `/inventory/movements/{id}` |

**GET filters:**

| Param | Type |
|-------|------|
| `product_id` | integer |
| `type` | string |
| `source` | string |
| `reference_id` | integer |

**POST body:**
```json
{
  "product_id": 5,
  "type": "in",
  "quantity": 50,
  "notes": "Purchase order #123"
}
```

Rules:

- branch is derived from authenticated device.
- stock changes apply to `branch_product.stock_quantity`.
- `in`: increment stock.
- `out`: decrement stock, rejected if insufficient.
- `adjustment`: set absolute stock value.

---

## 5. Internal Architecture

### Controllers
Located in `app/Http/Controllers/Api/`.

### Actions

| Action | Used by |
|---|---|
| `ProcessSale` | `SaleController@store` |
| `OpenCashSession` | `CashSessionController@open` |
| `CloseCashSession` | `CashSessionController@close` |
| `RecordInventoryMovement` | `InventoryMovementController@store` |

### Key Resources

| Resource | Embeds |
|---|---|
| `ProductResource` | `category`, `brand`, `branch_product` |
| `SaleResource` | `customer`, `cash_session`, `branch`, `sale_details` |
| `SavedCartResource` | `customer`, `cash_session`, `branch`, `items` |
| `CashSessionResource` | `branch`, `device` |
| `InventoryMovementResource` | `product`, `branch` |
| `DeviceResource` | `branch` |

### Key Patterns

- authenticated device defines branch context.
- stock operations use `lockForUpdate()` inside transactions.
- stock lives in `branch_product`, not `products`.
- `sku` is the global product identity.

---

## 6. Database Schema

### `products`
| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `name` | string(255) | |
| `description` | text | nullable |
| `cost_price` | decimal(10,2) | nullable, global |
| `sku` | string(255) | required, unique |
| `unit_measure` | string(20) | default `PZA` |
| `category_id` | FK -> categories | nullable |
| `brand_id` | FK -> brands | nullable |
| `created_at` / `updated_at` | timestamp | |

### `branches`
| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `name` | string(255) | unique |
| `code` | string(255) | unique, nullable |
| `address` | text | nullable |
| `is_active` | boolean | default true |

### `devices`
| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `branch_id` | FK -> branches | required |
| `name` | string | |
| `identifier` | string | unique |
| `secret_hash` | string | hashed secret |
| `is_active` | boolean | default true |
| `last_login_at` | timestamp | nullable |

### `branch_product`
| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `branch_id` | FK -> branches | required |
| `product_id` | FK -> products | required |
| `price` | decimal(10,2) | |
| `stock_quantity` | integer | default 0 |
| `min_stock` | integer | default 5 |
| `is_available` | boolean | default true |
| unique | composite | (`branch_id`, `product_id`) |

### `cash_sessions`
| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `branch_id` | FK -> branches | copied from device |
| `device_id` | FK -> devices | required |
| `status` | string | `open` or `closed` |
| `opening_balance` | decimal(10,2) | |
| `closing_balance` | decimal(10,2) | nullable |
| `opened_at` | timestamp | server-side |
| `closed_at` | timestamp | nullable |
| `notes` | text | nullable |

### `sales`
| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `customer_id` | FK -> customers | nullable |
| `cash_session_id` | FK -> cash_sessions | required |
| `branch_id` | FK -> branches | required |
| `payment_method` | string | `cash`, `card`, `transfer` |
| `subtotal` | decimal(10,2) | |
| `tax_amount` | decimal(10,2) | default 0 |
| `discount_amount` | decimal(10,2) | default 0 |
| `total_amount` | decimal(10,2) | |
| `status` | string | default `completed` |
| `sale_date` | timestamp | set server-side |

### `saved_carts`
| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `name` | string | |
| `customer_id` | FK -> customers | nullable |
| `cash_session_id` | FK -> cash_sessions | nullable |
| `branch_id` | FK -> branches | required |
| `discount_amount` | decimal(10,2) | default 0 |
| `status` | string | default `saved` |
| `notes` | text | nullable |

### `sale_details`
| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `sale_id` | FK -> sales | required |
| `product_id` | FK -> products | required |
| `quantity` | integer | |
| `unit_price` | decimal(10,2) | historical price |
| `tax_amount` | decimal(10,2) | |
| `subtotal` | decimal(10,2) | |
| `total` | decimal(10,2) | |

### `inventory_movements`
| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `product_id` | FK -> products | required |
| `branch_id` | FK -> branches | required |
| `type` | string | `in`, `out`, `adjustment` |
| `quantity` | integer | |
| `source` | string | `manual` or `sale` |
| `reference_id` | bigint | nullable |
| `notes` | text | nullable |

### `cash_movements`
| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `cash_session_id` | FK -> cash_sessions | required |
| `branch_id` | FK -> branches | copied from session |
| `type` | string | `in` or `out` |
| `category` | string | `sale`, `withdrawal`, `change`, `expense`, `refund`, `adjustment` |
| `amount` | decimal(10,2) | |
| `source` | string | `manual` or `sale` |
| `reference_id` | bigint | nullable |
| `notes` | text | nullable |
