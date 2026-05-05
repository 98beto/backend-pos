<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSavedCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'customer_id' => 'nullable|exists:customers,id',
            'cash_session_id' => 'nullable|exists:cash_sessions,id',
            'branch_id' => 'required|exists:branches,id',
            'discount_amount' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|string|in:saved,in_progress',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_amount' => 'required|numeric|min:0',
            'items.*.subtotal' => 'required|numeric|min:0',
            'items.*.total' => 'required|numeric|min:0',
        ];
    }
}
