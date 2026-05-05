<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OpenCashSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => 'required|exists:branches,id',
            'device_identifier' => 'required|string|max:100',
            'opening_balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ];
    }
}
