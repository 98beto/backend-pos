<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavedCartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'saved_cart_id',
        'product_id',
        'quantity',
        'unit_price',
        'tax_amount',
        'subtotal',
        'total',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function savedCart()
    {
        return $this->belongsTo(SavedCart::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
