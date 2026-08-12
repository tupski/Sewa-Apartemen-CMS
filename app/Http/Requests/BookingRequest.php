<?php

namespace App\Http\Requests;

use App\Models\Property;
use App\Services\BookingPricingService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'property_id' => ['required', 'integer', 'exists:properties,id'],
            'booking_type' => ['required', 'string', Rule::in(['daily', 'transit', 'weekly', 'monthly'])],
            'unit_type' => ['required', 'string', Rule::in(array_keys(Property::UNIT_TYPES))],
            'duration_hours' => ['nullable', 'integer', Rule::in(BookingPricingService::TRANSIT_BUCKETS)],
            'check_in' => ['required', 'date'],
            'check_out' => ['nullable', 'date', 'after_or_equal:check_in'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:20'],
            'customer_whatsapp' => ['nullable', 'string', 'max:20'],
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
            'property_id' => 'property',
            'booking_type' => 'jenis sewa',
            'unit_type' => 'tipe kamar',
            'duration_hours' => 'durasi transit',
            'check_in' => 'tanggal check-in',
            'check_out' => 'tanggal check-out',
            'customer_name' => 'nama lengkap',
            'customer_email' => 'email',
            'customer_phone' => 'nomor telepon',
            'customer_whatsapp' => 'nomor WhatsApp',
            'guests' => 'jumlah tamu',
            'message' => 'pesan',
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
            'duration_hours.in' => 'Durasi transit harus salah satu dari: 3, 6, 9, 12, atau 24 jam.',
            'unit_type.in' => 'Tipe kamar tidak valid.',
            'check_out.after_or_equal' => 'Tanggal check-out harus sama atau setelah tanggal check-in.',
            'guests.min' => 'Minimal 1 tamu.',
            'guests.max' => 'Maksimal 20 tamu per booking.',
        ];
    }
}
