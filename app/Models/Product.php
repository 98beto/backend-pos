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
        "unit_measure",
        "sku",
        "category_id",
        "brand_id",
    ];

    protected $casts = [
        "cost_price" => "decimal:2",
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

    public function branchProducts()
    {
        return $this->hasMany(BranchProduct::class);
    }

    public function currentBranchProduct()
    {
        return $this->hasOne(BranchProduct::class);
    }

    public function saleDetails()
    {
        return $this->hasMany(SaleDetail::class);
    }

    public function savedCartItems()
    {
        return $this->hasMany(SavedCartItem::class);
    }

}
