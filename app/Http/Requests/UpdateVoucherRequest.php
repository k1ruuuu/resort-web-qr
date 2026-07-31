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
            'addition_map' => ['nullable', 'array'],
            'addition_map.*' => ['nullable', 'integer', 'min:0', 'max:50'],
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function ($validator) {
            $additionMap = $this->input('addition_map');
            if (empty($additionMap) || !is_array($additionMap)) {
                return;
            }

            $grantedIds = [];

            if (is_array($this->input('facility_status'))) {
                foreach ($this->input('facility_status') as $id => $status) {
                    if ($status === 'granted') {
                        $grantedIds[] = (int) $id;
                    }
                }
            }

            if (empty($grantedIds)) {
                $voucher = $this->route('voucher');
                if ($voucher?->facility_template_id) {
                    $grantedIds = array_map('intval', explode(',', $voucher->facility_template_id));
                }
            }

            foreach (array_keys($additionMap) as $facilityId) {
                if (!in_array((int) $facilityId, $grantedIds, true)) {
                    $validator->errors()->add(
                        "addition_map.{$facilityId}",
                        'Addition can only be granted to facilities linked to this voucher.'
                    );
                }
            }
        });
    }
}
