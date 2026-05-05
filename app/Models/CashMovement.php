<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'cash_session_id',
        'branch_id',
        'type',
        'category',
        'amount',
        'source',
        'reference_id',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'reference_id' => 'integer',
    ];

    public function cashSession()
    {
        return $this->belongsTo(CashSession::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
