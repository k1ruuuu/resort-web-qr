<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RedeemVoucherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('vouchers.redeem') ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('qr_token') && ! $this->filled('qr_code')) {
            $this->merge(['qr_code' => $this->input('qr_token')]);
        }
    }

    public function rules(): array
    {
        return [
            'qr_code' => ['required', 'string', 'max:255'],
            'outlet_id' => ['required', 'exists:outlets,id'],
            'facility_template_id' => ['nullable', 'exists:facility_templates,id'], // Optional - will use outlet's facility if not provided
            'pax_used' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }
}
