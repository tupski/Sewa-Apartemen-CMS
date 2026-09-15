<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

/**
 * Bulk save of the Artivo-managed place categories from the property
 * Create/Edit screen ("Manage categories" panel).
 *
 * `slug` is required for every row but is only honored for NEW rows — the
 * controller never rewrites the slug of an existing row, because the slug is
 * the raw Geoapify key used for Places-API filtering and sync matching.
 */
class PlaceCategoryBulkRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'categories' => ['required', 'array', 'max:100'],
            'categories.*.id' => ['nullable', 'integer', 'exists:place_categories,id'],
            'categories.*.slug' => [
                'required',
                'string',
                'max:64',
                'regex:/^[a-z0-9_]+(\.[a-z0-9_]+)*$/',
                'distinct',
            ],
            'categories.*.name_id' => ['required', 'string', 'max:100'],
            'categories.*.name_en' => ['required', 'string', 'max:100'],
            'categories.*.icon' => ['nullable', 'string', 'max:100'],
            'categories.*.color' => ['nullable', 'string', 'max:7', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'categories.*.is_active' => ['required', 'boolean'],
            'categories.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ];
    }

    /**
     * This endpoint is fetch()-only (the property form's category panel), but
     * the app renders validation errors as redirects outside `api/*` — so the
     * JSON response is attached to the exception directly.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new ValidationException($validator, response()->json([
            'success' => false,
            'message' => $validator->errors()->first(),
            'errors' => $validator->errors()->toArray(),
        ], 422));
    }

    /**
     * Human-readable messages for the rules admins actually hit.
     */
    public function messages(): array
    {
        return [
            'categories.*.slug.distinct' => __('place_category.rows_share_slug'),
            'categories.*.slug.regex' => __('place_category.slug_format'),
            'categories.*.color.regex' => __('place_category.color_format'),
        ];
    }
}
