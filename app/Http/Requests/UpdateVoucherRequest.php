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
            'facility_template_ids' => ['nullable', 'array'],
            'facility_template_ids.*' => ['required', 'exists:facility_templates,id'],
            'pax_limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }
}
