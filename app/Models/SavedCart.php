<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavedCart extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'customer_id',
        'cash_session_id',
        'branch_id',
        'discount_amount',
        'status',
        'notes',
    ];

    protected $casts = [
        'discount_amount' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function cashSession()
    {
        return $this->belongsTo(CashSession::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function items()
    {
        return $this->hasMany(SavedCartItem::class);
    }
}
