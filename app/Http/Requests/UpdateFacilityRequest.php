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

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function ($validator) {
            $user = $this->user();

            if (!$user || $user->hasRole('super-admin')) {
                return;
            }

            $propertyIds = $user->properties()->pluck('property_id');

            if (!in_array((int) $this->input('property_id'), $propertyIds->map(fn ($id) => (int) $id)->all(), true)) {
                $validator->errors()->add('property_id', 'You do not have access to this property.');
            }
        });
    }
}
