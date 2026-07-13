<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOutletRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('facilities.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'property_id' => ['required', 'exists:properties,id'],
            'facility_template_id' => ['required', 'exists:facility_templates,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:32',
                Rule::unique('outlets', 'code')->where(function ($query) {
                    return $query->where('property_id', $this->property_id);
                }),
            ],
            'is_active' => ['boolean'],
        ];
    }
}
