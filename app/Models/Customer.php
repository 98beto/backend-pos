<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = ["name", "email", "phone", "address", "tax_id"];

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function savedCarts()
    {
        return $this->hasMany(SavedCart::class);
    }
}
