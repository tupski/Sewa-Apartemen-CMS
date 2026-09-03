<?php

namespace App\Http\Requests;

use App\Services\SafeHtmlService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PageRequest extends FormRequest
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
        $pageId = $this->route('page') ? $this->route('page')->id : null;

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('pages')->ignore($pageId),
            ],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'string'],
            'status' => ['required', 'string', Rule::in(['draft', 'published', 'scheduled'])],
            'is_homepage' => ['nullable', 'boolean'],
            'layout' => ['nullable', 'string', 'max:100'],
            'blocks' => ['nullable', 'array'],
            'seo' => ['nullable', 'array'],
            'seo.meta_title' => ['nullable', 'string', 'max:255'],
            'seo.meta_description' => ['nullable', 'string', 'max:320'],
            'seo.open_graph' => ['nullable', 'array'],
            'seo.open_graph.keywords' => ['nullable', 'string', 'max:500'],
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
            'title' => 'page title',
            'slug' => 'page slug',
            'excerpt' => 'page excerpt',
            'content' => 'page content',
            'status' => 'page status',
            'is_homepage' => 'homepage setting',
            'layout' => 'page layout',
            'blocks' => 'page blocks',
            'seo.meta_title' => 'meta title',
            'seo.meta_description' => 'meta description',
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
            'title.required' => 'The page title is required.',
            'slug.unique' => 'This slug is already in use. Please choose a different one.',
            'slug.regex' => 'The slug may only contain lowercase letters, numbers, and hyphens.',
            'content.required' => 'The page content is required.',
            'status.required' => 'Please select a page status.',
            'status.in' => 'The status must be either draft, published, or scheduled.',
        ];
    }

    /**
     * Sanitize admin-entered rich content before persistence (FIND-005).
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->merge([
                'content' => SafeHtmlService::sanitize($this->input('content')),
            ]);
        });
    }
}
