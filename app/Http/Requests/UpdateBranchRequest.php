<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $branchId = $this->route('branch')?->id;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('branches', 'name')->ignore($branchId),
            ],
            'code' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('branches', 'code')->ignore($branchId),
            ],
            'address' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
