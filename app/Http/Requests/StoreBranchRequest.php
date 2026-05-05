<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:branches,name',
            'code' => 'nullable|string|max:255|unique:branches,code',
            'address' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
