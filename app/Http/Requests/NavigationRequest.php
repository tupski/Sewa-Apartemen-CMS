<?php

namespace App\Http\Requests;

use App\Models\Navigation;
use Illuminate\Foundation\Http\FormRequest;

class NavigationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'type' => 'required|in:page,url,custom',
            'url' => 'required_if:type,url|nullable|string',
            'page_id' => 'required_if:type,page|nullable|exists:pages,id',
            'parent_id' => 'nullable|exists:navigations,id',
            'menu_location' => 'required|string|max:100',
            'target' => 'required|in:_self,_blank',
            'icon' => 'nullable|string|max:100',
            'order' => 'nullable|integer|min:0',
            'status' => 'required|in:active,inactive',
            'css_class' => 'nullable|string|max:255',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->parent_id && $this->route('navigation')) {
                $currentId = $this->route('navigation')->id;
                $parentId = $this->parent_id;

                // Prevent self-reference
                if ($currentId === $parentId) {
                    $validator->errors()->add('parent_id', 'A navigation item cannot be its own parent.');
                    return;
                }

                // Prevent circular reference
                if ($this->isDescendant($currentId, $parentId)) {
                    $validator->errors()->add('parent_id', 'Invalid parent selection: circular reference detected.');
                }
            }
        });
    }

    /**
     * Check if a navigation item is a descendant of another.
     *
     * @param int $ancestorId
     * @param int $descendantId
     * @return bool
     */
    protected function isDescendant(int $ancestorId, int $descendantId): bool
    {
        $navigation = Navigation::find($descendantId);

        if (!$navigation) {
            return false;
        }

        // Traverse up the tree
        while ($navigation) {
            if ($navigation->parent_id === $ancestorId) {
                return true;
            }
            $navigation = $navigation->parent;
        }

        return false;
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'page_id' => 'page',
            'parent_id' => 'parent navigation',
            'menu_location' => 'menu location',
            'css_class' => 'CSS class',
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
            'url.required_if' => 'The URL field is required when type is URL.',
            'page_id.required_if' => 'The page field is required when type is page.',
        ];
    }
}
