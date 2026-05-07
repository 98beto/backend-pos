<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|exists:products,id',
            'type'       => 'required|in:in,out,adjustment',
            // For 'in'/'out' the minimum meaningful quantity is 1.
            // For 'adjustment' it may be 0 (setting stock to zero is valid).
            // min:0 covers all three cases; the controller already blocks 'out'
            // when stock is insufficient.
            'quantity'   => 'required|integer|min:0',
            'notes'      => 'nullable|string',
        ];
    }
}
