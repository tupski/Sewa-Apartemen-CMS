<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PropertyRequest extends FormRequest
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
    /**
     * Strip thousand separators from money inputs before validation.
     *
     * The admin price inputs (see <x-money-input>) normally arrive as plain
     * integers because the JS handler strips separators on submit. This is a
     * server-side belt-and-suspenders: if a formatted value like "150.000"
     * ever reaches here (JS disabled, programmatic post), we normalise it back
     * to a clean integer so the `numeric` rule and integer storage still pass.
     */
    protected function prepareForValidation(): void
    {
        $prices = $this->input('prices');

        if (is_array($prices)) {
            array_walk_recursive($prices, function (&$value) {
                if (is_string($value) && $value !== '') {
                    $stripped = preg_replace('/\D/', '', $value);
                    $value = $stripped === '' ? null : $stripped;
                }
            });

            $this->merge(['prices' => $prices]);
        }
    }

    public function rules(): array
    {
        $propertyId = $this->route('property') ? $this->route('property')->id : null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('properties')->ignore($propertyId),
            ],
            'description' => ['nullable', 'string'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'featured_image_id' => ['nullable', 'integer', 'exists:media,id'],
            'status' => ['required', 'string', Rule::in(['draft', 'published'])],
            'seo' => ['nullable', 'array'],
            // BUG FIX: SEO fields are persisted to the polymorphic SeoMetadata
            // morph (nested seo[...] names), mirroring the Post pattern. The old
            // flat meta_title/meta_description rules never reached the morph.
            'seo.meta_title' => ['nullable', 'string', 'max:255'],
            'seo.meta_description' => ['nullable', 'string', 'max:320'],
            'seo.canonical_url' => ['nullable', 'url', 'max:2048'],
            'is_featured' => ['nullable', 'boolean'],
            'order' => ['nullable', 'integer', 'min:0'],
            'max_days' => ['nullable', 'integer', 'min:1'],
            'max_guests' => ['nullable', 'integer', 'min:1', 'max:20'],
            'checkin_time' => ['nullable', 'string', 'max:5'],
            'checkout_time' => ['nullable', 'string', 'max:5'],
            'checkin_method' => ['nullable', 'string', 'max:255'],
            'required_documents' => ['nullable', 'array'],
            'required_documents.*' => ['nullable', 'string', 'max:255'],
            'nearby_places' => ['nullable', 'array'],
            'nearby_places.*.name' => ['nullable', 'string', 'max:255'],
            'nearby_places.*.category' => ['nullable', 'string', 'in:Nearby Places,Transportation,Entertainment/Attraction,Others'],
            'nearby_places.*.distance_km' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'amenities' => ['nullable', 'array'],
            'amenities.*' => ['integer', 'exists:amenities,id'],
            'unit_types' => ['nullable', 'array'],
            'unit_types.*' => ['string', Rule::in(array_keys(\App\Models\Property::UNIT_TYPES))],
            'weekend_days' => ['nullable', 'array'],
            'weekend_days.*' => ['integer', 'between:0,6'],
            'prices' => ['nullable', 'array'],
            'prices.*.*' => ['nullable', 'numeric', 'min:0'],
            'photo_categories' => ['nullable'],
            'gallery_uploads' => ['nullable', 'array'],
            'gallery_uploads.*' => ['array'],
            // Mirror the client-side uploader (JPEG, PNG, WebP, GIF · max 10 MB).
            // `image` + `mimes` validate against the real (sniffed) file type, so a
            // renamed/spoofed extension is rejected server-side (defence in depth).
            'gallery_uploads.*.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:10240'],
            'gallery_media' => ['nullable', 'array'],
            'gallery_media.*' => ['array'],
            'gallery_media.*.*' => ['integer', 'exists:media,id'],
            'deleted_photo_ids' => ['nullable'],
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
            'name' => 'property name',
            'slug' => 'property slug',
            'description' => 'property description',
            'address' => 'property address',
            'city' => 'city',
            'province' => 'province',
            'postal_code' => 'postal code',
            'latitude' => 'latitude',
            'longitude' => 'longitude',
            'featured_image_id' => 'featured image',
            'status' => 'property status',
            'seo.meta_title' => 'meta title',
            'seo.meta_description' => 'meta description',
            'is_featured' => 'featured status',
            'order' => 'display order',
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
            'name.required' => 'The property name is required.',
            'slug.unique' => 'This slug is already in use. Please choose a different one.',
            'slug.regex' => 'The slug may only contain lowercase letters, numbers, and hyphens.',
            'status.required' => 'Please select a property status.',
            'status.in' => 'The status must be either draft or published.',
            'latitude.between' => 'The latitude must be between -90 and 90.',
            'longitude.between' => 'The longitude must be between -180 and 180.',
        ];
    }

    /**
     * Sanitize admin-entered rich content before persistence (FIND-005).
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->merge([
                'description' => \App\Services\SafeHtmlService::sanitize($this->input('description')),
            ]);
        });
    }
}
