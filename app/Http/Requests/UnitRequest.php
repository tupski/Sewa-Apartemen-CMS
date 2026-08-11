<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UnitRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $unitId = $this->route('unit') ? $this->route('unit')->id : null;

        return [
            'property_id' => ['required', 'integer', 'exists:properties,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('units')->ignore($unitId),
            ],
            'unit_type' => ['required', 'string', Rule::in(['studio', '1br', '2br', '3br', 'penthouse'])],
            'floor' => ['nullable', 'integer', 'min:0'],
            'size_sqm' => ['nullable', 'numeric', 'min:0'],
            'bedrooms' => ['nullable', 'integer', 'min:0'],
            'bathrooms' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'price_per_night' => ['nullable', 'numeric', 'min:0'],
            'price_per_month' => ['nullable', 'numeric', 'min:0'],
            'price_per_year' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'string', Rule::in(['available', 'booked', 'maintenance'])],
            'featured_image_id' => ['nullable', 'integer', 'exists:media,id'],
            'amenities' => ['nullable', 'array'],
            'amenities.*' => ['integer', 'exists:amenities,id'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'property_id' => 'property',
            'name' => 'unit name',
            'slug' => 'unit slug',
            'unit_type' => 'unit type',
            'floor' => 'floor number',
            'size_sqm' => 'size (sqm)',
            'bedrooms' => 'number of bedrooms',
            'bathrooms' => 'number of bathrooms',
            'description' => 'unit description',
            'price_per_night' => 'price per night',
            'price_per_month' => 'price per month',
            'price_per_year' => 'price per year',
            'status' => 'unit status',
            'featured_image_id' => 'featured image',
            'amenities' => 'amenities',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'property_id.required' => 'Please select a property.',
            'property_id.exists' => 'The selected property does not exist.',
            'name.required' => 'The unit name is required.',
            'slug.unique' => 'This slug is already in use. Please choose a different one.',
            'slug.regex' => 'The slug may only contain lowercase letters, numbers, and hyphens.',
            'unit_type.required' => 'Please select a unit type.',
            'unit_type.in' => 'The unit type must be one of: studio, 1br, 2br, 3br, penthouse.',
            'status.required' => 'Please select a unit status.',
            'status.in' => 'The status must be one of: available, booked, maintenance.',
        ];
    }
}
