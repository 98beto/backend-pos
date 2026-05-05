<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCashMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'required|string|in:in,out',
            'category' => 'required|string|in:sale,withdrawal,change,expense,refund,adjustment',
            'amount' => 'required|numeric|gt:0',
            'notes' => 'nullable|string',
        ];
    }
}
