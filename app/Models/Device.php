<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Device extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'name',
        'identifier',
        'secret_hash',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'secret_hash',
        'remember_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function cashSessions()
    {
        return $this->hasMany(CashSession::class);
    }
}
