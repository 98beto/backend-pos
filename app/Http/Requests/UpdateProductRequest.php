<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'cost_price' => 'nullable|numeric|min:0',
            'sku' => ['required', 'string', 'max:255', Rule::unique('products', 'sku')->ignore($this->product)],
            'unit_measure' => 'nullable|string|max:50',
            'category_id' => 'nullable|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
        ];
    }
}
