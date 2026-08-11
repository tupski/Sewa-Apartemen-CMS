<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class BookingRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'unit_id' => ['required', 'integer', 'exists:units,id'],
            'property_id' => ['required', 'integer', 'exists:properties,id'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:20'],
            'customer_whatsapp' => ['nullable', 'string', 'max:20'],
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after_or_equal:check_in'],
            'guests' => ['required', 'integer', 'min:1', 'max:20'],
            'message' => ['nullable', 'string', 'max:1000'],
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
            'unit_id' => 'unit',
            'property_id' => 'property',
            'customer_name' => 'full name',
            'customer_email' => 'email address',
            'customer_phone' => 'phone number',
            'customer_whatsapp' => 'WhatsApp number',
            'check_in' => 'check-in date',
            'check_out' => 'check-out date',
            'guests' => 'number of guests',
            'message' => 'message',
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
            'check_in.after_or_equal' => 'The check-in date must be today or in the future.',
            'check_out.after_or_equal' => 'The check-out date must be on or after the check-in date.',
            'guests.min' => 'You must have at least 1 guest.',
            'guests.max' => 'Maximum 20 guests allowed per booking.',
        ];
    }
}
