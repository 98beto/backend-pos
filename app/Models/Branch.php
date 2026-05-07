<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'address',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function cashSessions()
    {
        return $this->hasMany(CashSession::class);
    }

    public function devices()
    {
        return $this->hasMany(Device::class);
    }

    public function branchProducts()
    {
        return $this->hasMany(BranchProduct::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function savedCarts()
    {
        return $this->hasMany(SavedCart::class);
    }

    public function cashMovements()
    {
        return $this->hasMany(CashMovement::class);
    }
}
