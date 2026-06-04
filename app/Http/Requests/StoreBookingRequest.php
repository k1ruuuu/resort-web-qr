<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('bookings.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'property_id' => ['required', 'exists:properties,id'],
            'guest_id' => ['required', 'exists:guests,id'],
            'room_id' => ['nullable', 'exists:rooms,id'],
            'booking_code' => ['nullable', 'string', 'max:32'],
            'reference' => ['nullable', 'string', 'max:32'],
            'source' => ['nullable', 'string', 'max:64'],
            'room_label' => ['nullable', 'string', 'max:255'],
            'check_in' => ['required', 'date'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'expected_arrival' => ['nullable', 'date'],
            'expected_departure' => ['nullable', 'date'],
            'nights' => ['nullable', 'integer', 'min:1'],
            'adults' => ['required', 'integer', 'min:1'],
            'children' => ['nullable', 'integer', 'min:0'],
            'extra_beds' => ['nullable', 'integer', 'min:0'],
            'total_pax' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'string', 'in:pending,checked_in,checked_out,cancelled'],
            'pms_voucher_ref' => ['nullable', 'string', 'max:64'],
            'facilities' => ['nullable', 'array'],
            'facilities.*.facility_template_id' => ['required', 'exists:facility_templates,id'],
            'facilities.*.start_date' => ['nullable', 'date'],
            'facilities.*.end_date' => ['nullable', 'date'],
            'facilities.*.quota_total' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
