<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'room_id' => [
                'nullable',
                Rule::exists('rooms', 'id')->where(function ($query) {
                    $query->where('property_id', $this->input('property_id'));
                }),
            ],
            'booking_code' => ['nullable', 'string', 'max:32'],
            'reference' => ['nullable', 'string', 'max:32'],
            'source' => ['nullable', 'string', 'max:64'],
            'room_label' => ['nullable', 'string', 'max:255'],
            'check_in' => ['required', 'date'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'expected_arrival' => ['nullable', 'date'],
            'expected_departure' => ['nullable', 'date'],
            'adults' => ['required', 'integer', 'min:1'],
            'children' => ['nullable', 'integer', 'min:0'],
            'extra_beds' => ['nullable', 'integer', 'min:0'],
            'arrangement_code' => ['nullable', 'string', 'max:64'],
            'pms_voucher_ref' => ['nullable', 'string', 'max:64'],
            'facilities' => ['nullable', 'array'],
            'facilities.*.facility_template_id' => [
                'required',
                Rule::exists('facility_templates', 'id')->where(function ($query) {
                    $query->where('property_id', $this->input('property_id'));
                }),
            ],
            'facilities.*.start_date' => ['nullable', 'date'],
            'facilities.*.end_date' => ['nullable', 'date'],
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function ($validator) {
            $user = $this->user();

            if (!$user || $user->hasRole('super-admin')) {
                return;
            }

            $propertyIds = $user->properties()->pluck('property_id');

            // M-15: property-scoped users may only book into their own properties
            if (!in_array((int) $this->input('property_id'), $propertyIds->map(fn ($id) => (int) $id)->all(), true)) {
                $validator->errors()->add('property_id', 'You do not have access to this property.');
            }
        });
    }
}
