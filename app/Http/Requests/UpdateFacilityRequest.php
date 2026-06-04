<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFacilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('facilities.manage') ?? false;
    }

    public function rules(): array
    {
        $facilityId = $this->route('facility')?->id;

        return [
            'property_id' => ['required', 'exists:properties,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:32',
                Rule::unique('facility_templates', 'code')
                    ->where(function ($query) {
                        return $query->where('property_id', $this->property_id);
                    })
                    ->ignore($facilityId),
            ],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
