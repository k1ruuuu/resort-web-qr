<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateVoucherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'booking_id' => ['nullable', 'exists:bookings,id'],
            'property_id' => ['required_without:booking_id', 'exists:properties,id'],
            'guest_name' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'in:temporary,standard'],
            'expiration_type' => ['nullable', 'in:hour,date'],
            'expiration_value' => ['nullable', 'string'],
            'valid_date' => ['nullable', 'date'],
            'facility_selection' => ['nullable', 'in:single,multiple,all'],
            'facility_template_ids' => ['nullable', 'array'],
            'facility_template_ids.*' => ['required', 'exists:facility_templates,id'],
            'pax_limit' => ['nullable', 'integer', 'min:1', 'max:50'],  // SECURITY FIX: Added max:50
        ];
    }
}
