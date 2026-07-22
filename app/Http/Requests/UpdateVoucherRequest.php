<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVoucherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'facility_status' => ['nullable', 'array'],
            'facility_status.*' => ['required', 'string', 'in:granted,not_granted'],
            'addition' => ['nullable', 'integer', 'min:0', 'max:50'],
            'addition_facility_ids' => ['nullable', 'array'],
            'addition_facility_ids.*' => ['required', 'integer', 'exists:facility_templates,id'],
        ];
    }
}
