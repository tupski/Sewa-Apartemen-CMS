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
            // New booking method: duration number + unit (jam/malam)
            'unit' => ['nullable', 'string', Rule::in(['jam', 'malam'])],
            'duration' => ['nullable', 'integer', 'min:1', 'max:365'],
            // Legacy fields (kept for backward compatibility / admin APIs)
            'booking_type' => ['nullable', 'string', Rule::in(['daily', 'transit', 'weekly', 'monthly'])],
            'unit_type' => ['required', 'string', Rule::in(array_keys(Property::UNIT_TYPES))],
            'duration_hours' => ['nullable', 'integer', 'min:1', 'max:24'],
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_in_time' => ['nullable', 'string', 'max:5', 'regex:/^([01][0-9]|2[0-3]):[0-5][0-9]$/'],
            'check_out' => ['nullable', 'date', 'after_or_equal:check_in'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:25'],
            'customer_whatsapp' => ['nullable', 'string', 'max:25'],
            'guests' => ['required', 'integer', 'min:1', 'max:20'],
            'message' => ['nullable', 'string', 'max:1000'],
            // Promo rate
            'promo_rate_id' => ['nullable', 'integer', 'exists:promo_rates,id'],
            // Voucher
            'voucher_code' => ['nullable', 'string', 'max:50'],
            'voucher_id' => ['nullable', 'integer', 'exists:vouchers,id'],
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
            'unit' => 'satuan durasi',
            'duration' => 'durasi',
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
            'promo_rate_id' => 'kode promo',
            'voucher_code' => 'kode voucher',
            'voucher_id' => 'voucher',
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
            'unit.in' => 'Satuan durasi harus Jam atau Malam.',
            'duration.min' => 'Durasi minimal 1.',
            'duration.max' => 'Durasi terlalu lama.',
            'duration_hours.in' => 'Durasi transit harus salah satu dari: 3, 6, 9, 12, atau 24 jam.',
            'unit_type.in' => 'Tipe kamar tidak valid.',
            'check_out.after_or_equal' => 'Tanggal check-out harus sama atau setelah tanggal check-in.',
            'guests.min' => 'Minimal 1 tamu.',
            'guests.max' => 'Maksimal 20 tamu per booking.',
        ];
    }
}
