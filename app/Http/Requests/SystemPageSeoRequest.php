<?php

namespace App\Http\Requests;

use App\Models\SystemPage;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for the System Pages SEO editor (admin Pages → System Pages).
 *
 * Only SEO metadata is editable here — the route identity (`key`, `label`) is
 * owned by {@see SystemPage::REGISTRY} and is never mass-assigned
 * from a request.
 */
class SystemPageSeoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Route-level `['auth', 'verified', 'admin']` middleware already gates this;
     * the check is repeated here as defence in depth.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'seo' => ['nullable', 'array'],
            'seo.meta_title' => ['nullable', 'string', 'max:255'],
            'seo.meta_description' => ['nullable', 'string', 'max:320'],
            'seo.canonical_url' => ['nullable', 'url', 'max:2048'],
            'seo.index_status' => ['nullable', 'boolean'],
            'seo.open_graph' => ['nullable', 'array'],
            'seo.open_graph.title' => ['nullable', 'string', 'max:255'],
            'seo.open_graph.description' => ['nullable', 'string', 'max:320'],
            'seo.open_graph.image' => ['nullable', 'string', 'max:2048'],
            'seo.open_graph.type' => ['nullable', 'string', 'max:50'],
            'seo.twitter' => ['nullable', 'array'],
            'seo.twitter.title' => ['nullable', 'string', 'max:255'],
            'seo.twitter.description' => ['nullable', 'string', 'max:320'],
            'seo.twitter.image' => ['nullable', 'string', 'max:2048'],
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
            'seo.meta_title' => 'meta title',
            'seo.meta_description' => 'meta description',
            'seo.canonical_url' => 'canonical URL',
            'seo.open_graph.image' => 'Open Graph image',
            'seo.twitter.image' => 'Twitter image',
        ];
    }
}
