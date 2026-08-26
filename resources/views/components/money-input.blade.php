{{--
    Reusable "Rp"-prefixed money input.

    Renders a text input (NOT type=number, so it can display thousand
    separators) with a non-editable "Rp" adornment on the left. The value is
    formatted live with dot thousand separators by the global [data-money]
    handler in resources/js/app.js, and stripped back to a plain integer right
    before the form is submitted — so the server always receives clean digits.

    Props:
      name         (required) input name attribute
      id           optional id attribute
      value        raw integer value (formatted on load by JS)
      placeholder  placeholder text
      inputClass   extra classes for the <input>
      wrapperClass extra classes for the wrapping <div>
      prefix       adornment text (default "Rp")
--}}
@props([
    'name',
    'id' => null,
    'value' => '',
    'placeholder' => '',
    'inputClass' => 'w-full py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm text-right',
    'wrapperClass' => '',
    'prefix' => null,
])

<div class="relative {{ $wrapperClass }}">
    <span class="pointer-events-none select-none absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 dark:text-gray-500 text-sm">
        {{ $prefix ?? __('admin.money_prefix') }}
    </span>
    <input type="text"
           inputmode="numeric"
           data-money
           name="{{ $name }}"
           @if($id) id="{{ $id }}" @endif
           value="{{ $value }}"
           placeholder="{{ $placeholder }}"
           class="pl-9 {{ $inputClass }}">
</div>
