<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Owner-facing notifications for booking lifecycle events.
 *
 * Delivery: a single outbound webhook (URL stored in settings.notification_webhook).
 * Compatible with Fonnte, Wablas, OneSender, n8n, Make, or any HTTP endpoint that
 * accepts JSON. The same payload is also appended to the application log so an
 * operator without a configured webhook can still see events.
 *
 * Fire-and-forget by design: a webhook failure must never break a booking action.
 * The service logs failures and gives up after one retry.
 */
class BookingNotificationService
{
    public const EVENT_CREATED = 'booking.created';
    public const EVENT_CONFIRMED = 'booking.confirmed';
    public const EVENT_CANCELLED = 'booking.cancelled';
    public const EVENT_COMPLETED = 'booking.completed';

    /**
     * Send a notification for the given booking event.
     */
    public static function send(string $event, Booking $booking): void
    {
        $booking->loadMissing('property');

        $payload = [
            'event' => $event,
            'sent_at' => now()->toIso8601String(),
            'booking' => self::serialize($booking),
        ];

        // Always log so an operator can audit events even without a webhook.
        Log::info("booking.notification.{$event}", $payload);

        $url = trim((string) SettingsService::get('notification_webhook', ''));
        if ($url === '') {
            return;
        }

        $secret = (string) SettingsService::get('notification_webhook_secret', '');

        $headers = [
            'Accept' => 'application/json',
            'User-Agent' => 'SewaApartemenCMS/1.0',
            'X-Webhook-Event' => $event,
        ];
        if ($secret !== '') {
            $headers['X-Webhook-Signature'] = hash_hmac('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE), $secret);
        }

        try {
            $response = Http::timeout(5)->withHeaders($headers)->post($url, $payload);

            if ($response->successful()) {
                log_activity('webhook_sent', "{$event} → {$url} ({$response->status()})");
                return;
            }

            // One retry on 5xx
            if ($response->serverError()) {
                $retry = Http::timeout(5)->withHeaders($headers)->post($url, $payload);
                if ($retry->successful()) {
                    log_activity('webhook_sent', "{$event} → {$url} ({$retry->status()}, retry)");
                    return;
                }
                self::logFailure($event, $url, $retry->status());
                return;
            }

            self::logFailure($event, $url, $response->status());
        } catch (\Throwable $e) {
            self::logFailure($event, $url, 0, $e->getMessage());
        }
    }

    /**
     * Compact, JSON-safe representation of a booking for webhook consumers.
     */
    protected static function serialize(Booking $booking): array
    {
        $property = $booking->property;

        return [
            'id' => $booking->id,
            'code' => $booking->code,
            'status' => $booking->status,
            'booking_type' => $booking->booking_type,
            'unit_type' => $booking->unit_type,
            'unit_label' => $property?->typeLabel($booking->unit_type) ?? $booking->unit_type,
            'duration_hours' => $booking->duration_hours,
            'check_in' => $booking->check_in?->toIso8601String(),
            'check_out' => $booking->check_out?->toIso8601String(),
            'guests' => $booking->guests,
            'total_price' => (float) $booking->total_price,
            'deposit_amount' => (float) $booking->deposit_amount,
            'currency' => SettingsService::get('currency', 'IDR'),
            'customer' => [
                'name' => $booking->customer_name,
                'email' => $booking->customer_email,
                'phone' => $booking->customer_phone,
                'whatsapp' => $booking->customer_whatsapp,
            ],
            'message' => $booking->message,
            'property' => $property ? [
                'id' => $property->id,
                'name' => $property->name,
                'slug' => $property->slug,
                'city' => $property->city,
            ] : null,
            'admin_url' => route('admin.bookings.show', $booking, false),
        ];
    }

    protected static function logFailure(string $event, string $url, int $status, ?string $error = null): void
    {
        $msg = $error
            ? "{$event} → {$url} failed: {$error}"
            : "{$event} → {$url} failed with status {$status}";

        Log::warning("booking.notification.failed: {$msg}");
        log_activity('webhook_failed', $msg);
    }
}
