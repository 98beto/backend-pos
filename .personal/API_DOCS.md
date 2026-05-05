# Backend API — Documentation (POS System)

Built with **Laravel 13** + **PostgreSQL**.
No authentication - local single-user system.

---

## 1. Standard Response Format

The API wraps responses in a JSON envelope, but the exact success shape varies by action.

**Success - list / show (200):**
```json
{
  "success": true,
  "data": { }
}
```

**Success - create / update (200 / 201):**
```json
{
  "success": true,
  "message": "Operation completed successfully.",
  "data": { }
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

**Business rule error (422) - simple message:**
```json
{
  "success": false,
  "message": "Cannot delete customer with associated sales records."
}
```

**Business rule error (422) - stock validation style:**
```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "items": ["Insufficient stock for product: \"Cola\". Available: 2, Requested: 5."]
  }
}
```

> In transactional endpoints that catch `ValidationException` manually, the exact top-level `message` may vary from Laravel's default text. The important stock detail is available inside `errors`.

**Not found (404):**
```json
{
  "success": false,
  "message": "Record not found."
}
```

**Server error (500):**
```json
{
  "success": false,
  "message": "An unexpected error occurred."
}
```

> All error types - including 404s and model-not-found - return JSON. This is handled globally in `bootstrap/app.php` via `withExceptions`.
> Route-not-found responses return `Resource not found.` and model-not-found responses return `Record not found.`
> The exact 404 message depends on whether the route is missing or the model binding failed.
> `GET /cash-sessions/current` is a custom case: when no open session exists it returns `404` with `No open cash session found.`
> Some transactional controllers return endpoint-specific `500` messages such as `An unexpected error occurred while processing the sale.` or `An unexpected error occurred while recording the movement.`

**Paginated list responses** include Laravel's standard pagination envelope inside `data`:
```json
{
  "success": true,
  "data": {
    "data": [ ],
    "links": { "first": "...", "last": "...", "prev": null, "next": "..." },
    "meta": { "current_page": 1, "last_page": 3, "per_page": 20, "total": 45 }
  }
}
```

> This paginated shape is used by all paginated `index` endpoints in the API, including brands, categories, products, customers, suppliers, sales, sale details, cash sessions, and inventory movements.

---

## 2. Endpoints

All routes are prefixed with `/api`. Controllers live in `app/Http/Controllers/Api/`.
Branch-aware transactional responses include both `branch_id` and an embedded `branch` object when the controller eager-loads the relation.

---

### Dashboard - `DashboardController`

| Method | Path | Description |
|--------|------|-------------|
| GET | `/dashboard` | Key metrics summary for the main screen |

**Query filters:**
- `branch_id` - optional, limits today's sales and open-session summary to a specific branch

**Response `data`:**
```json
{
  "today": {
    "sales_count": 12,
    "revenue": 1540.00,
    "items_sold": 48
  },
  "cash_session": {
    "id": 3,
    "opened_at": "2026-03-26 08:00:00",
    "opening_balance": 500.00
  },
  "inventory": {
    "total_products": 120,
    "active_products": 115,
    "low_stock_count": 7
  }
}
```

> `cash_session` is `null` when no session is currently open.
> `today` counts only `status = 'completed'` sales with `sale_date` on today's date.
> When `branch_id` is provided, both today's sales and the open cash session summary are scoped to that branch.
> The `inventory` snapshot remains global even when `branch_id` is present, because product stock is still global in the current model.

---

### Brands - `BrandController`

| Method | Path | Description |
|--------|------|-------------|
| GET | `/brands` | List all brands (paginated, ordered by name) |
| POST | `/brands` | Create a brand |
| GET | `/brands/{id}` | Get a single brand |
| PUT/PATCH | `/brands/{id}` | Update a brand |
| DELETE | `/brands/{id}` | Delete a brand |

**POST / PUT body:**
```json
{
  "name": "Nike",
  "description": "Optional description",
  "img_url": "https://..."
}
```

**Validation rules:**
- `name` - required, string, max 255, unique (ignores self on update)
- `description` - nullable, string
- `img_url` - nullable, string, max 255

---

### Branches - `BranchController`

| Method | Path | Description |
|--------|------|-------------|
| GET | `/branches` | List branches (paginated, ordered by name) |
| POST | `/branches` | Create a branch |
| GET | `/branches/{id}` | Get a single branch |
| PUT/PATCH | `/branches/{id}` | Update a branch |

**POST / PUT body:**
```json
{
  "name": "Sucursal Centro",
  "code": "CENTRO",
  "address": "Av. Principal 123",
  "is_active": true
}
```

**Validation rules:**
- `name` - required, string, max 255, unique (ignores self on update)
- `code` - nullable, string, max 255, unique (ignores self on update)
- `address` - nullable, string
- `is_active` - sometimes, boolean

---

### Categories - `CategoryController`

| Method | Path | Description |
|--------|------|-------------|
| GET | `/categories` | List all categories (paginated, ordered by name) |
| POST | `/categories` | Create a category |
| GET | `/categories/{id}` | Get a single category |
| PUT/PATCH | `/categories/{id}` | Update a category |
| DELETE | `/categories/{id}` | Delete a category |

**POST / PUT body:**
```json
{
  "name": "Beverages",
  "description": "Optional description"
}
```

**Validation rules:**
- `name` - required, string, max 255, unique (ignores self on update)
- `description` - nullable, string

---

### Products - `ProductController`

| Method | Path | Description |
|--------|------|-------------|
| GET | `/products` | List products (paginated by 50, ordered by name). Supports filters - see below. |
| GET | `/products/low-stock` | Products where `stock_quantity <= min_stock` (paginated, ordered by stock asc) |
| POST | `/products` | Create a product |
| GET | `/products/{id}` | Get a single product (eager-loads `category` + `brand`) |
| PUT/PATCH | `/products/{id}` | Update a product |
| DELETE | `/products/{id}` | Delete a product |

**GET `/products` - query filters:**

| Param | Type | Description |
|-------|------|-------------|
| `search` | string | Case-insensitive (`ILIKE`) search across `name`, `sku`, and `barcode` |
| `category_id` | integer | Filter by category |
| `brand_id` | integer | Filter by brand |
| `is_active` | 0 \| 1 | Filter active or inactive products |
| `low_stock` | 1 | Only products where `stock_quantity <= min_stock` |

All filters are optional and combinable. An unmatched filter returns an empty list, never a 422.

> `GET /products/low-stock` uses `Product::scopeLowStock()` and is equivalent to `GET /products?low_stock=1` but as a dedicated endpoint for dashboard / alert widgets.
> Both `/products` and `/products/low-stock` return paginated payloads inside the top-level `data` key with this shape: `{ data: [...], links: {...}, meta: {...} }`.
> Text search uses PostgreSQL `ILIKE`, so matches are case-insensitive.

**Paginated response shape:**
```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 20,
        "name": "Apagador sencillo",
        "stock_quantity": 38,
        "category": {
          "id": 4,
          "name": "Electricidad"
        },
        "brand": null
      }
    ],
    "links": {
      "first": "http://localhost/api/products?page=1",
      "last": "http://localhost/api/products?page=2",
      "prev": null,
      "next": "http://localhost/api/products?page=2"
    },
    "meta": {
      "current_page": 1,
      "last_page": 2,
      "per_page": 50,
      "total": 65
    }
  }
}
```

**POST / PUT body:**
```json
{
  "name": "Coca-Cola 500ml",
  "description": "Optional",
  "cost_price": 8.50,
  "price": 15.00,
  "stock_quantity": 100,
  "min_stock": 10,
  "unit_measure": "pza",
  "barcode": "7501000000001",
  "sku": "COC-500",
  "is_active": true,
  "category_id": 1,
  "brand_id": 2
}
```

**Validation rules:**
- `name` - required, string, max 255
- `description` - nullable, string
- `cost_price` - nullable, numeric, min 0
- `price` - required, numeric, min 0
- `stock_quantity` - required, integer, min 0
- `min_stock` - sometimes, integer, min 0 (defaults to 5 on create)
- `unit_measure` - nullable, string, max 50
- `barcode` - nullable, string, max 255, unique (ignores self on update)
- `sku` - nullable, string, max 255, unique (ignores self on update)
- `is_active` - sometimes, boolean
- `category_id` - nullable, must exist in `categories` if provided
- `brand_id` - nullable, must exist in `brands` if provided

---

### Customers - `CustomerController`

| Method | Path | Description |
|--------|------|-------------|
| GET | `/customers` | List customers (paginated, ordered by name). Supports `?search=`. |
| POST | `/customers` | Create a customer |
| GET | `/customers/{id}` | Get a single customer |
| PUT/PATCH | `/customers/{id}` | Update a customer |
| DELETE | `/customers/{id}` | Delete a customer (blocked if customer has sales) |

**GET `/customers` - query filters:**

| Param | Type | Description |
|-------|------|-------------|
| `search` | string | Case-insensitive (`ILIKE`) search across `name`, `email`, and `phone` |

**POST / PUT body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "phone": "555-1234",
  "address": "123 Main St",
  "tax_id": "RFC123456"
}
```

**Validation rules:**
- `name` - required, string, max 255
- `email` - nullable, valid email, unique (ignores self on update)
- `phone` - nullable, string, max 20
- `address` - nullable, string
- `tax_id` - nullable, string, max 50

**Business rule:** `DELETE /customers/{id}` returns `422` if the customer has associated sales records.

---

### Suppliers - `SupplierController`

| Method | Path | Description |
|--------|------|-------------|
| GET | `/suppliers` | List suppliers (paginated, ordered by name). Supports `?search=`. |
| POST | `/suppliers` | Create a supplier |
| GET | `/suppliers/{id}` | Get a single supplier |
| PUT/PATCH | `/suppliers/{id}` | Update a supplier |
| DELETE | `/suppliers/{id}` | Delete a supplier |

**GET `/suppliers` - query filters:**

| Param | Type | Description |
|-------|------|-------------|
| `search` | string | Case-insensitive (`ILIKE`) search across `name`, `email`, and `phone` |

**POST / PUT body:**
```json
{
  "name": "Distribuidora XYZ",
  "contact_person": "Juan Garcia",
  "email": "contacto@xyz.com",
  "phone": "555-9999",
  "address": "Av. Industrial 456",
  "credit_days": 30,
  "bank_info": "BBVA / CLABE: 012345678901234567"
}
```

**Validation rules:**
- `name` - required, string, max 255
- `contact_person` - nullable, string, max 255
- `email` - nullable, valid email, unique (ignores self on update)
- `phone` - nullable, string, max 20
- `address` - nullable, string
- `credit_days` - nullable, integer, min 0 (defaults to 0 on create)
- `bank_info` - nullable, string

---

### Sales - `SaleController`

| Method | Path | Description |
|--------|------|-------------|
| GET | `/sales` | List sales (paginated, latest first, eager-loads `customer`). Supports filters - see below. |
| POST | `/sales` | Process a new sale |
| GET | `/sales/{id}` | Get a single sale (eager-loads `customer`, `cashSession`, `saleDetails.product`) |

**GET `/sales` - query filters:**

| Param | Type | Description |
|-------|------|-------------|
| `search` | string | Matches sale `id` (when numeric) or customer `name` with case-insensitive `ILIKE`. Searching `Publico general` / `Público general` also matches sales where `customer_id` is `null`. |
| `date_from` | date `Y-m-d` | Sales on or after this date (`whereDate`) |
| `date_to` | date `Y-m-d` | Sales on or before this date (`whereDate`) |
| `cash_session_id` | integer | Sales belonging to a specific session |
| `branch_id` | integer | Sales belonging to a specific branch |
| `customer_id` | integer | Sales by a specific customer |
| `payment_method` | string | `cash`, `card`, or `transfer` |
| `status` | string | e.g. `completed` |

**POST body:**
```json
{
  "customer_id": 1,
  "branch_id": 1,
  "cash_session_id": 3,
  "payment_method": "cash",
  "discount_amount": 5.00,
  "items": [
    {
      "product_id": 7,
      "quantity": 2,
      "unit_price": 25.00,
      "tax_amount": 4.00,
      "subtotal": 50.00,
      "total": 54.00
    }
  ]
}
```

**Validation rules:**
- `customer_id` - nullable, must exist in `customers`
- `branch_id` - required, must exist in `branches`
- `cash_session_id` - required, must exist in `cash_sessions` **with `status = 'open'`**
- `payment_method` - required, one of: `cash`, `card`, `transfer`
- `discount_amount` - sometimes, numeric, min 0
- `items` - required array, min 1 item
- `items.*.product_id` - required, must exist in `products`
- `items.*.quantity` - required, integer, min 1
- `items.*.unit_price / tax_amount / subtotal / total` - required, numeric, min 0

**Business rules:**
- `customer_id` is optional - omit for walk-in (general public) sales.
- `cash_session_id` must belong to an **open** session. Attempting to sell on a closed session returns `422`.
- `cash_session_id` must belong to the same `branch_id` sent in the request. Cross-branch sales return `422`.
- Stock availability is checked inside a `DB::transaction` with `lockForUpdate()` before any write occurs. If any item lacks stock, the entire transaction is rolled back.
- Sale totals (`subtotal`, `tax_amount`, `total_amount`) are **recalculated server-side** from the submitted items - frontend totals are never trusted.
- `sale_date` is set explicitly to `now()` server-side.
- Historical prices are stored in `sale_details` at the time of sale and are never re-read from the live product.
- Stock is decremented atomically via `Product::decrement()`.
- Each sold item also creates an `inventory_movements` record with `type = out`, `source = sale`, and `reference_id = sale.id` inside the same transaction.
- Cash sales also create a `cash_movements` record with `type = in`, `category = sale`, `source = sale`, and `reference_id = sale.id` inside the same transaction.

---

### Saved Carts - `SavedCartController`

| Method | Path | Description |
|--------|------|-------------|
| GET | `/saved-carts` | List saved carts (paginated, latest first). Returns only `status = saved` by default. Supports `?branch_id=`. |
| POST | `/saved-carts` | Save a cart without affecting stock |
| GET | `/saved-carts/{id}` | Get a single saved cart (eager-loads `customer`, `cashSession`, `items.product`) |
| PATCH | `/saved-carts/{id}/recover` | Mark a saved cart as `in_progress` and load it into the POS safely |
| PUT/PATCH | `/saved-carts/{id}` | Replace a saved cart and all of its items |
| DELETE | `/saved-carts/{id}` | Delete a saved cart |

> Saved carts are for parking multiple carts before checkout. They do **not** decrement stock and they do **not** require a payment method.
> `GET /saved-carts` returns only carts with `status = saved` unless you pass `?status=in_progress`.

**POST / PUT body:**
```json
{
  "name": "Cliente mostrador 1",
  "customer_id": 1,
  "branch_id": 1,
  "cash_session_id": 3,
  "discount_amount": 5.00,
  "status": "saved",
  "notes": "Regresa en unos minutos",
  "items": [
    {
      "product_id": 7,
      "quantity": 2,
      "unit_price": 25.00,
      "tax_amount": 4.00,
      "subtotal": 50.00,
      "total": 54.00
    }
  ]
}
```

**Validation rules:**
- `name` - required, string, max 255
- `customer_id` - nullable, must exist in `customers`
- `branch_id` - required, must exist in `branches`
- `cash_session_id` - nullable, must exist in `cash_sessions`
- `discount_amount` - sometimes, numeric, min 0
- `status` - sometimes, one of: `saved`, `in_progress`
- `notes` - nullable, string
- `items` - required array, min 1 item
- `items.*.product_id` - required, must exist in `products`
- `items.*.quantity` - required, integer, min 1
- `items.*.unit_price / tax_amount / subtotal / total` - required, numeric, min 0

**Response `data`:**
```json
{
  "id": 5,
  "name": "Cliente mostrador 1",
  "customer_id": 1,
  "cash_session_id": 3,
  "status": "saved",
  "subtotal": 50.00,
  "tax_amount": 4.00,
  "discount_amount": 5.00,
  "total_amount": 49.00,
  "notes": "Regresa en unos minutos",
  "customer": { },
  "cash_session": { },
  "items": [
    {
      "id": 10,
      "saved_cart_id": 5,
      "product_id": 7,
      "quantity": 2,
      "unit_price": 25.00,
      "tax_amount": 4.00,
      "subtotal": 50.00,
      "total": 54.00,
      "product": { }
    }
  ]
}
```

**Business rules:**
- Saving a cart never decrements stock.
- `cash_session_id` is optional so a cart can survive across session boundaries.
- If `cash_session_id` is present, it must belong to the same `branch_id`.
- `status` supports `saved` and `in_progress`.
- `PATCH /saved-carts/{id}/recover` marks the cart as `in_progress` instead of deleting it on retrieval.
- Recommended POS flow: recover -> work the cart in the UI -> either update it back to `saved` or delete it after successful checkout.
- On update, existing items are replaced by the submitted `items` array.

---

### Sale Details - `SaleDetailController` (read-only)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/sale-details` | List sale details. Supports `?sale_id=` and `?branch_id=` |
| GET | `/sale-details/{id}` | Get a single sale detail (eager-loads `product`; `sale` is loaded internally but not serialized) |

> Sale details are created automatically when a sale is processed via `POST /sales`. They cannot be created or modified directly.
> `branch_id` filters through the parent sale, so only details from sales in that branch are returned.
> `SaleDetailResource` now also includes `branch_id` and an embedded `branch` object derived from the parent sale when the relation is eager-loaded.

---

### Cash Sessions - `CashSessionController`

| Method | Path | Description |
|--------|------|-------------|
| GET | `/cash-sessions` | List all sessions (paginated, latest first). Supports `?branch_id=`. |
| GET | `/cash-sessions/current` | Get the currently open session for a branch/device pair |
| POST | `/cash-sessions/open` | Open a new session |
| GET | `/cash-sessions/{id}/movements` | List cash movements for a session |
| POST | `/cash-sessions/{id}/movements` | Record a manual cash movement |
| GET | `/cash-sessions/{id}` | Get a single session. The controller eager-loads `sales`, but `CashSessionResource` does not serialize them. |
| POST | `/cash-sessions/{id}/close` | Close a session |

**GET `/cash-sessions/current` query params:**
- `branch_id` - required
- `device_identifier` - required

**POST `/cash-sessions/open` body:**
```json
{
  "branch_id": 1,
  "device_identifier": "POS-01",
  "opening_balance": 500.00,
  "notes": "Morning shift"
}
```

**POST `/cash-sessions/{id}/close` body:**
```json
{
  "closing_balance": 1350.00,
  "notes": "End of day"
}
```

**Close response `data`:**
```json
{
  "session": { },
  "expected_balance": 1340.00,
  "actual_balance": 1350.00,
  "difference": 10.00
}
```
**POST `/cash-sessions/{id}/movements` body:**
```json
{
  "type": "out",
  "category": "withdrawal",
  "amount": 150.00,
  "notes": "Retiro para gastos"
}
```

> `difference` is positive for surplus, negative for shortage. `expected_balance` is calculated as `opening_balance + sum(in) - sum(out)` from `cash_movements`.
> `CashSessionResource` serializes `opening_balance` / `closing_balance`.
> Unlike the dashboard summary, cash-session endpoints use the field name `opening_balance`.

**Business rules:**
- Multiple sessions may be open at the same time, but only one per `branch_id + device_identifier`. Opening another on the same pair returns `422`.
- `opened_at` is set server-side to `now()` on open.
- `closed_at` is set server-side to `now()` on close.
- Attempting to close an already-closed session returns `422`.
- `GET /cash-sessions/current` returns `422` when `branch_id` or `device_identifier` is missing.
- Manual movements can only be created while the session is open.
- `category = sale` is reserved for automatic movements created by cash sales.
- `category = adjustment` requires `notes`.

---

### Cash Movements - `CashMovementController`

| Method | Path | Description |
|--------|------|-------------|
| GET | `/cash-sessions/{cashSession}/movements` | List cash movements for a session (paginated, latest first) |
| POST | `/cash-sessions/{cashSession}/movements` | Record a manual cash movement |

**POST body:**
```json
{
  "type": "out",
  "category": "expense",
  "amount": 75.00,
  "notes": "Pago de taxi"
}
```

**Validation rules:**
- `type` - required, one of: `in`, `out`
- `category` - required, one of: `sale`, `withdrawal`, `change`, `expense`, `refund`, `adjustment`
- `amount` - required, numeric, greater than 0
- `notes` - nullable, string

**Business rules:**
- The route uses the session's `branch_id`; branch is not accepted from the request body.
- Closed sessions reject new cash movements with `422`.
- Manual requests may not use `category = sale`.
- `adjustment` requires `notes`.
- Cash-movement business rules are centralized in `app/Support/CashMovementRules.php`.

---

### Inventory Movements - `InventoryMovementController`

| Method | Path | Description |
|--------|------|-------------|
| GET | `/inventory/movements` | List movements. Supports filters - see below. |
| POST | `/inventory/movements` | Record a manual movement |
| GET | `/inventory/movements/{id}` | Get a single movement (eager-loads `product`) |

**GET `/inventory/movements` - query filters:**

| Param | Type | Description |
|-------|------|-------------|
| `product_id` | integer | Movements for a specific product |
| `branch_id` | integer | Movements recorded for a specific branch |
| `type` | string | `in`, `out`, or `adjustment` |
| `source` | string | Filter by origin, currently `manual` or `sale` |
| `reference_id` | integer | Filter by source record id, e.g. a sale id |

**POST body:**
```json
{
  "product_id": 5,
  "branch_id": 1,
  "type": "in",
  "quantity": 50,
  "notes": "Purchase order #123"
}
```

**Validation rules:**
- `product_id` - required, must exist in `products`
- `branch_id` - required, must exist in `branches`
- `type` - required, one of: `in`, `out`, `adjustment`
- `quantity` - required, integer, min 0 (`adjustment` to zero is valid; controller blocks `out` when stock is insufficient)
- `notes` - nullable, string

**Response `data`:**
```json
{
  "id": 10,
  "product_id": 5,
  "branch_id": 1,
  "branch": { },
  "type": "out",
  "quantity": 2,
  "source": "sale",
  "reference_id": 123,
  "notes": "Sale #123",
  "product": { },
  "created_at": "2026-04-23 18:00:00",
  "updated_at": "2026-04-23 18:00:00"
}
```

**Business rules by type:**

| Type | Stock effect |
|------|-------------|
| `in` | `stock_quantity += quantity` (increment) |
| `out` | `stock_quantity -= quantity` (decrement). Returns `422` if insufficient stock. |
| `adjustment` | `stock_quantity = quantity` (absolute override) |

> All stock updates run inside a `DB::transaction` with `lockForUpdate()`.
> Manual movements created via `POST /inventory/movements` are stored with `source = manual` and `reference_id = null`.
> Manual and automatic inventory movements now also store `branch_id` for operational traceability, even though stock remains global.
> Sales generate automatic `out` movements with `source = sale`, `branch_id = sale.branch_id`, and `reference_id` set to the sale id.
> `GET /inventory/movements/{id}` uses Laravel route model binding. If the movement does not exist, the API returns `404` with `Record not found.`

---

## 3. Internal Architecture

### Controllers
Located in `app/Http/Controllers/Api/`. No `create()` or `edit()` methods (those are HTML-only). All controllers are resource-style.

### FormRequests
Located in `app/Http/Requests/`. Every write operation has a dedicated request class. Controllers receive pre-validated data - no inline `$request->validate()` calls.

| Request class | Used by |
|---|---|
| `StoreBrandRequest` / `UpdateBrandRequest` | `BrandController` |
| `StoreCategoryRequest` / `UpdateCategoryRequest` | `CategoryController` |
| `StoreProductRequest` / `UpdateProductRequest` | `ProductController` |
| `StoreCustomerRequest` / `UpdateCustomerRequest` | `CustomerController` |
| `StoreSupplierRequest` / `UpdateSupplierRequest` | `SupplierController` |
| `StoreSaleRequest` | `SaleController` |
| `StoreSavedCartRequest` / `UpdateSavedCartRequest` | `SavedCartController` |
| `StoreInventoryMovementRequest` | `InventoryMovementController` |
| `OpenCashSessionRequest` / `CloseCashSessionRequest` | `CashSessionController` |
| `StoreBranchRequest` / `UpdateBranchRequest` | `BranchController` |
| `StoreCashMovementRequest` | `CashMovementController` |

### API Resources
Located in `app/Http/Resources/`. Every model is serialized through a resource class - raw Eloquent models are never returned directly. Resources use `whenLoaded()` for conditional relationship embedding.

| Resource | Embeds |
|---|---|
| `BrandResource` | - |
| `BranchResource` | - |
| `CategoryResource` | - |
| `ProductResource` | `category`, `brand` |
| `CustomerResource` | - |
| `SupplierResource` | - |
| `SaleResource` | `customer`, `cash_session`, `branch`, `sale_details` |
| `SavedCartResource` | `customer`, `cash_session`, `branch`, `items` |
| `SavedCartItemResource` | `product` |
| `SaleDetailResource` | `product`, `branch` |
| `CashSessionResource` | `branch` |
| `CashMovementResource` | `branch` |
| `InventoryMovementResource` | `product`, `branch` |

### Error Handling
Configured globally in `bootstrap/app.php` via `withExceptions`. All errors return JSON regardless of the `Accept` header:

| Exception | HTTP code |
|---|---|
| `NotFoundHttpException` (unknown route) | 404 |
| `ModelNotFoundException` (route model binding) | 404 |
| `ValidationException` (FormRequest failures) | 422 |

### Key Patterns
- `Rule::unique()->ignore($id)` on all update validations - no string concatenation.
- Index endpoints use pagination - most use `paginate(20)`, while `GET /products` uses `paginate(50)`.
- `ValidationException` caught separately from `\Throwable` in transactional controllers.
- `lockForUpdate()` inside transactions for all stock operations.
- Atomic `increment()` / `decrement()` for stock changes.
- `DB::transaction()` in `SaleController@store` and `InventoryMovementController@store`.
- `sale_date` set explicitly to `now()` in `SaleController@store` - never relies on DB default alone.
- `Product::scopeLowStock()` reused by both `GET /products?low_stock=1` and `GET /products/low-stock`.
- `$request->filled('is_active')` used instead of `$request->is_active` to correctly handle `is_active=0`.

---

## 4. Database Schema

### `categories`
| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `name` | string(255) | |
| `description` | text | nullable |
| `created_at` / `updated_at` | timestamp | |

### `brands`
| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `name` | string(255) | |
| `description` | text | nullable |
| `img_url` | string(255) | nullable |
| `created_at` / `updated_at` | timestamp | |

### `products`
| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `name` | string(255) | |
| `description` | text | nullable |
| `cost_price` | decimal(10,2) | nullable |
| `price` | decimal(10,2) | |
| `stock_quantity` | integer | default 0 |
| `min_stock` | integer | default 5 |
| `unit_measure` | string(20) | default `PZA` |
| `barcode` | string(255) | unique, nullable |
| `sku` | string(255) | unique, nullable |
| `is_active` | boolean | default false |
| `category_id` | FK -> categories | nullable |
| `brand_id` | FK -> brands | nullable |
| `created_at` / `updated_at` | timestamp | |

### `customers`
| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `name` | string(255) | |
| `email` | string(255) | unique, nullable |
| `phone` | string(255) | nullable |
| `address` | text | nullable |
| `tax_id` | string(255) | nullable |
| `created_at` / `updated_at` | timestamp | |

### `suppliers`
| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `name` | string(255) | |
| `contact_person` | string(255) | nullable |
| `email` | string(255) | unique, nullable |
| `phone` | string(255) | nullable |
| `address` | text | nullable |
| `credit_days` | integer | default 0 |
| `bank_info` | text | nullable |
| `created_at` / `updated_at` | timestamp | |

### `branches`
| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `name` | string(255) | unique |
| `code` | string(255) | unique, nullable |
| `address` | text | nullable |
| `is_active` | boolean | default true |
| `created_at` / `updated_at` | timestamp | |

### `cash_sessions`
| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `branch_id` | FK -> branches | required after backfill |
| `device_identifier` | string | required after backfill |
| `status` | string | `open` or `closed` |
| `opening_balance` | decimal(10,2) | |
| `closing_balance` | decimal(10,2) | nullable |
| `opened_at` | timestamp | set server-side on open |
| `closed_at` | timestamp | nullable, set server-side on close |
| `notes` | text | nullable |
| `created_at` / `updated_at` | timestamp | |
> PostgreSQL enforces a partial unique index so only one `open` session may exist per `branch_id + device_identifier`.

### `sales`
| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `sale_date` | timestamp | Set explicitly to `now()` in `SaleController@store`. Indexed (explicit). |
| `customer_id` | FK -> customers | nullable (walk-in sales). Indexed via FK. |
| `cash_session_id` | FK -> cash_sessions | must be an open session. Indexed via FK. |
| `branch_id` | FK -> branches | required |
| `payment_method` | string | `cash`, `card`, or `transfer` |
| `status` | string | default `completed` |
| `subtotal` | decimal(10,2) | |
| `tax_amount` | decimal(10,2) | default 0 |
| `discount_amount` | decimal(10,2) | default 0 |
| `total_amount` | decimal(10,2) | |
| `created_at` / `updated_at` | timestamp | |

### `saved_carts`
| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `name` | string(255) | |
| `customer_id` | FK -> customers | nullable |
| `cash_session_id` | FK -> cash_sessions | nullable |
| `branch_id` | FK -> branches | required |
| `discount_amount` | decimal(10,2) | default 0 |
| `status` | string | default `saved` |
| `notes` | text | nullable |
| `created_at` / `updated_at` | timestamp | |

### `saved_cart_items`
| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `saved_cart_id` | FK -> saved_carts | cascade delete |
| `product_id` | FK -> products | indexed via FK |
| `quantity` | integer | |
| `unit_price` | decimal(10,2) | |
| `tax_amount` | decimal(10,2) | default 0 |
| `subtotal` | decimal(10,2) | |
| `total` | decimal(10,2) | |
| `created_at` / `updated_at` | timestamp | |

### `sale_details`
| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `sale_id` | FK -> sales | cascade delete. Indexed via FK. |
| `product_id` | FK -> products | Indexed via FK. |
| `quantity` | integer | |
| `unit_price` | decimal(10,2) | historical price at time of sale |
| `tax_amount` | decimal(10,2) | historical tax at time of sale |
| `subtotal` | decimal(10,2) | historical subtotal at time of sale |
| `total` | decimal(10,2) | historical total at time of sale |
| `created_at` / `updated_at` | timestamp | |

### `inventory_movements`
| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `product_id` | FK -> products | Indexed via FK. |
| `branch_id` | FK -> branches | required after backfill |
| `type` | string | `in`, `out`, or `adjustment` |
| `quantity` | integer | |
| `source` | string | `manual` or `sale` |
| `reference_id` | bigint | nullable. For `source = sale`, stores the related sale id |
| `notes` | text | nullable |
| `created_at` / `updated_at` | timestamp | |

### `cash_movements`
| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `cash_session_id` | FK -> cash_sessions | cascade delete |
| `branch_id` | FK -> branches | copied from the session |
| `type` | string | `in` or `out` |
| `category` | string | `sale`, `withdrawal`, `change`, `expense`, `refund`, `adjustment` |
| `amount` | decimal(10,2) | |
| `source` | string | `manual` or `sale` |
| `reference_id` | bigint | nullable. For `source = sale`, stores the related sale id |
| `notes` | text | nullable |
| `created_at` / `updated_at` | timestamp | |

> All FK columns (`->foreignId()->constrained()`) already have implicit indexes created by Laravel/PostgreSQL. The only explicit index added via migration `2026_03_26_151911_add_performance_indexes` is `sales.sale_date`, which has no automatic index.
