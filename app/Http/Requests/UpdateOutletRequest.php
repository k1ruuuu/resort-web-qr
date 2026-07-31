<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOutletRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('facilities.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'property_id' => ['required', 'exists:properties,id'],
            'facility_template_ids' => ['required', 'array', 'min:1'],
            'facility_template_ids.*' => [
                'integer',
                Rule::exists('facility_templates', 'id')->where(function ($query) {
                    $query->where('property_id', $this->input('property_id'));
                }),
            ],
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:32',
                Rule::unique('outlets', 'code')
                    ->where(fn ($q) => $q->where('property_id', $this->property_id))
                    ->ignore($this->route('outlet')),
            ],
            'is_active' => ['boolean'],
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
