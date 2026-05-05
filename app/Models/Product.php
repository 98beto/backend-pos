<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        "name",
        "description",
        "cost_price",
        "price",
        "stock_quantity",
        "min_stock",
        "unit_measure",
        "barcode",
        "sku",
        "is_active",
        "category_id",
        "brand_id",
    ];

    protected $casts = [
        "cost_price" => "decimal:2",
        "price"      => "decimal:2",
        "is_active"  => "boolean",
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function inventoryMovements()
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function saleDetails()
    {
        return $this->hasMany(SaleDetail::class);
    }

    public function savedCartItems()
    {
        return $this->hasMany(SavedCartItem::class);
    }

    /**
     * Scope: products where stock_quantity is at or below min_stock.
     */
    public function scopeLowStock($query)
    {
        return $query->whereColumn('stock_quantity', '<=', 'min_stock');
    }
}
