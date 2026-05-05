# Pendientes y inconsistencias — Backend API

Este archivo documenta lo que falta implementar, las inconsistencias detectadas entre el código y el schema, y las mejoras pendientes.
Actualizar este archivo conforme se vayan resolviendo los ítems.

---

## Prioridad Alta — Funcionalidad faltante

> Todos los ítems de prioridad alta han sido resueltos. Ver sección **Resuelto ✅**.

---

## Prioridad Media — Inconsistencias schema vs código

> Todos los ítems de prioridad media han sido resueltos. Ver sección **Resuelto ✅**.

---

## Prioridad Baja — Mejoras y polish

### P-1: `CategoryController` y `BrandController` eliminan sin verificar productos asociados
**Archivos:** `CategoryController.php` (destroy), `BrandController.php` (destroy)

Al eliminar una categoría o marca, los productos que la referencian quedan con `category_id = null` / `brand_id = null` (el FK es nullable con SET NULL implícito).
El cliente no recibe ninguna advertencia.
**Fix sugerido:** Agregar un check similar al de `CustomerController`:
```php
if ($category->products()->exists()) {
    return response()->json(['success' => false, 'message' => '...'], 422);
}
```

---

### P-2: `CashSession` no tiene `scopeOpen()`
**Archivo:** `app/Models/CashSession.php`

La query `CashSession::where('status', 'open')` se repite manualmente en varios métodos del controller (`open`, `current`) y en `DashboardController`.
**Fix:** Agregar un scope reutilizable:
```php
public function scopeOpen($query)
{
    return $query->where('status', 'open');
}
```

---

### P-3: `Supplier` sin relación con `Product`
**Archivo:** `app/Models/Supplier.php`, `database/migrations/*_create_products_table.php`

No existe `supplier_id` en la tabla `products` ni relación `Supplier → Product`.
Si se quiere saber de qué proveedor viene cada producto, hay que agregar:
- Columna `supplier_id` nullable FK en la migración de `products`
- Relación `hasMany(Product::class)` en `Supplier`
- Relación `belongsTo(Supplier::class)` en `Product`

---

### P-4: Sin factories ni seeders de dominio
**Archivos:** `database/seeders/`, `database/factories/`

Solo existe `UserFactory` y `DatabaseSeeder` con un usuario de prueba.
Un `migrate:fresh --seed` deja la base completamente vacía.
**Fix:** Crear factories y un seeder para datos de demo:
- `CategoryFactory`, `BrandFactory`, `ProductFactory`
- `CustomerFactory`, `SupplierFactory`
- `CashSessionFactory`
- `DatabaseSeeder` que los llame en orden correcto (respetando FKs)

---

### P-5: `cost_price` expuesto en `ProductResource`
**Archivo:** `app/Http/Resources/ProductResource.php`

`cost_price` es el precio de compra (sensible comercialmente). Se expone en todas las respuestas de productos.
Como la API no tiene autenticación, cualquier llamada puede verlo.
No es un problema bloqueante dado que es un sistema local, pero vale documentarlo.

---

### P-6: Lógica de negocio acoplada a los controllers
**Archivos:** `SaleController.php`, `InventoryMovementController.php`, `CashSessionController.php`

La validación de stock, cálculo de totales, apertura/cierre de caja y actualización de inventario viven directamente en los controllers. Esto los hace difíciles de testear, reutilizar y mantener conforme crezca el sistema.
**Fix sugerido:** Extraer a una capa de `Actions` o `Services`:
```
app/Actions/Sales/ProcessSale.php
app/Actions/Inventory/RecordMovement.php
app/Actions/CashSessions/OpenSession.php
app/Actions/CashSessions/CloseSession.php
```
Cada action recibe los datos validados, contiene la lógica (transacción, locks, reglas de negocio) y devuelve el modelo resultante. El controller solo orquesta: llama al action y devuelve la respuesta.

---

### P-7: Riesgo de race condition al abrir caja
**Archivo:** `CashSessionController.php` (`open`)

El check `CashSession::where('status', 'open')->exists()` antes de crear una nueva sesión no es atómico. En escenarios concurrentes (aunque improbables en un POS local) dos requests simultáneos podrían pasar el check al mismo tiempo y crear dos sesiones abiertas.
**Fix sugerido:** Usar una restricción `unique` parcial en la BD, o ejecutar el check + create dentro de un `DB::transaction()` con lock.

---

### P-8: `SaleController@store` no maneja `product_id` inexistente
**Archivo:** `SaleController.php` (`store`)

`Product::lockForUpdate()->find($item['product_id'])` puede devolver `null` si el producto fue eliminado entre la validación del `FormRequest` y la ejecución de la transacción. Acceder a `$product->stock_quantity` en ese caso lanzaría un error 500 sin mensaje claro.
**Fix sugerido:** Cambiar `find()` por `findOrFail()` dentro de la transacción, o verificar explícitamente que `$product` no sea `null` antes de continuar.

---

### P-9: Índices insuficientes para búsqueda LIKE en productos
**Archivos:** `database/migrations/`, `ProductController.php` (`index`)

Las búsquedas `LIKE "%term%"` sobre `products.name`, `products.sku` y `products.barcode` no se benefician de índices B-Tree estándar en PostgreSQL. Con catálogos grandes esto puede volverse lento.
**Fix sugerido:** Habilitar la extensión `pg_trgm` en PostgreSQL y agregar índices GIN/GIST:
```sql
CREATE EXTENSION IF NOT EXISTS pg_trgm;
CREATE INDEX products_name_trgm_idx ON products USING GIN (name gin_trgm_ops);
CREATE INDEX products_sku_trgm_idx  ON products USING GIN (sku gin_trgm_ops);
```
Alternativamente, agregar un índice normal sobre `barcode` y `sku` si la búsqueda es por coincidencia exacta.

---

## Resuelto ✅

| # | Descripción | Fecha |
|---|-------------|-------|
| F-1 | `ProductController@index` — filtros por `search`, `category_id`, `brand_id`, `is_active`, `low_stock` | 2026-03-26 |
| F-2 | `SaleController@index` — filtros `?date_from`, `?date_to`, `?cash_session_id`, `?customer_id`, `?payment_method`, `?status`, `?search` | 2026-03-26 |
| F-3 | `GET /products/low-stock` — endpoint dedicado + `Product::scopeLowStock()` | 2026-03-26 |
| F-4 | `CustomerController@index` y `SupplierController@index` — filtro `?search=` por nombre/email/teléfono | 2026-03-26 |
| F-5 | `InventoryMovementController@index` — filtro `?type=` | 2026-03-26 |
| F-6 | `GET /dashboard` — `DashboardController::summary()` con ventas del día, sesión activa e inventario | 2026-03-26 |
| I-1 | `category_id`, `brand_id`, `sku` — cambiados de `required` a `nullable` en FormRequests para alinear con la migración | 2026-03-26 |
| I-2 | `payment_method` — ahora valida `in:cash,card,transfer` en `StoreSaleRequest` | 2026-03-26 |
| I-3 | `StoreInventoryMovementRequest` — `quantity` cambiado a `min:0` para permitir ajuste a cero | 2026-03-26 |
| I-4 | `SaleController@store` — `sale_date` ahora se establece explícitamente con `now()` | 2026-03-26 |
| I-5 | Migración `2026_03_26_151911_add_performance_indexes` — único índice real faltante: `sales.sale_date`. FK columns ya tienen índice implícito por `->foreignId()->constrained()` | 2026-03-26 |
| B-1 | Sin manejador de errores JSON — HTML en 404/500 | 2026-03-25 |
| B-2 | `StoreSaleRequest` permitía ventas a sesiones cerradas | 2026-03-25 |
| B-3 | `InventoryMovement` sin `$casts` — `quantity` como string | 2026-03-25 |
| B-4 | `SupplierController@index` con double-wrap en respuesta | 2026-03-25 |
| R-1 | Modelo/controlador `Suppliers` renombrado a `Supplier` | 2026-03-25 |
| R-2 | `CashSessionController@open` no seteaba `opened_at` | 2026-03-25 |
| R-3 | `CashSession` sin casts para `opened_at` / `closed_at` | 2026-03-25 |
| R-4 | `ProductController` ignoraba `description`, `cost_price`, `unit_measure`, `is_active` | 2026-03-25 |
| R-5 | Validación inline en controllers → extraída a FormRequests | 2026-03-25 |
| R-6 | Modelos serializados directamente → API Resources implementados | 2026-03-25 |

