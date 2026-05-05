<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        "sale_date",
        "customer_id",
        "cash_session_id",
        "branch_id",
        "tax_amount",
        "subtotal",
        "discount_amount",
        "total_amount",
        "payment_method",
        "status",
    ];

    protected $casts = [
        "sale_date"       => "datetime",
        "tax_amount"      => "decimal:2",
        "subtotal"        => "decimal:2",
        "discount_amount" => "decimal:2",
        "total_amount"    => "decimal:2",
    ];

    public function cashSession()
    {
        return $this->belongsTo(CashSession::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function saleDetails()
    {
        return $this->hasMany(SaleDetail::class);
    }
}
