<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email' => ['nullable', 'email', Rule::unique('suppliers', 'email')->ignore($this->supplier)],
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'credit_days' => 'nullable|integer|min:0',
            'bank_info' => 'nullable|string',
        ];
    }
}
