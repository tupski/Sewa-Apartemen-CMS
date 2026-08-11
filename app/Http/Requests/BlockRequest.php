<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BlockRequest extends FormRequest
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
        $blockId = $this->route('block') ? $this->route('block')->id : null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:100'],
            'identifier' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9_-]+$/',
                Rule::unique('blocks')->where(function ($query) {
                    return $query->where('area', $this->input('area'));
                })->ignore($blockId),
            ],
            'content' => ['required', 'array'],
            'order' => ['nullable', 'integer', 'min:0'],
            'area' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'string', Rule::in(['active', 'inactive'])],
            'settings' => ['nullable', 'array'],
            'pages' => ['nullable', 'array'],
            'pages.*' => ['integer', 'exists:pages,id'],
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
            'name' => 'block name',
            'type' => 'block type',
            'identifier' => 'block identifier',
            'content' => 'block content',
            'order' => 'display order',
            'area' => 'block area',
            'status' => 'block status',
            'settings' => 'block settings',
            'pages' => 'assigned pages',
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
            'name.required' => 'The block name is required.',
            'type.required' => 'Please select a block type.',
            'identifier.required' => 'The block identifier is required.',
            'identifier.unique' => 'This identifier is already used in the same area. Please choose a different one.',
            'identifier.regex' => 'The identifier may only contain lowercase letters, numbers, hyphens, and underscores.',
            'content.required' => 'The block content is required.',
            'content.array' => 'The block content must be a valid JSON object.',
            'status.required' => 'Please select a block status.',
            'status.in' => 'The status must be either active or inactive.',
            'pages.*.exists' => 'One or more selected pages do not exist.',
        ];
    }
}
