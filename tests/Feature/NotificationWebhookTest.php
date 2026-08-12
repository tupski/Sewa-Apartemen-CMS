<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Services\BookingNotificationService;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NotificationWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Clean state
        SettingsService::set('notification_webhook', '');
        SettingsService::set('notification_webhook_secret', '');
    }

    private function fakeBooking(): Booking
    {
        $b = new Booking();
        $b->id = 99;
        $b->code = 'BK-TEST-0001';
        $b->status = 'pending';
        $b->booking_type = 'daily';
        $b->unit_type = 'studio';
        $b->duration_hours = null;
        $b->check_in = now();
        $b->check_out = now()->addDay();
        $b->guests = 2;
        $b->total_price = 750000;
        $b->deposit_amount = 225000;
        $b->customer_name = 'Unit Test';
        $b->customer_phone = '6281234567890';
        $b->customer_whatsapp = '6281234567890';
        $b->customer_email = null;
        $b->message = 'ping';
        return $b;
    }

    public function test_no_webhook_set_does_not_throw(): void
    {
        BookingNotificationService::send(BookingNotificationService::EVENT_CREATED, $this->fakeBooking());
        Http::assertNothingSent();
        $this->assertTrue(true);
    }

    public function test_sends_to_configured_url_with_signature(): void
    {
        Http::fake(['https://hook.example.com/*' => Http::response(['ok' => true], 200)]);
        SettingsService::set('notification_webhook', 'https://hook.example.com/booking');
        SettingsService::set('notification_webhook_secret', 'shhh');

        BookingNotificationService::send(BookingNotificationService::EVENT_CONFIRMED, $this->fakeBooking());

        Http::assertSent(function ($request) {
            $body = json_decode($request->body(), true);
            $event = $request->header('X-Webhook-Event');
            $event = is_array($event) ? ($event[0] ?? null) : $event;

            return $request->url() === 'https://hook.example.com/booking'
                && $request->method() === 'POST'
                && $event === BookingNotificationService::EVENT_CONFIRMED
                && ! empty($request->header('X-Webhook-Signature'))
                && ($body['event'] ?? null) === BookingNotificationService::EVENT_CONFIRMED
                && ($body['booking']['code'] ?? null) === 'BK-TEST-0001'
                && ($body['booking']['total_price'] ?? null) === 750000;
        });
    }

    public function test_5xx_triggers_one_retry(): void
    {
        Http::fake(['https://hook.fail.example/*' => Http::sequence()->push('boom', 503)->push(['ok' => true], 200)]);
        SettingsService::set('notification_webhook', 'https://hook.fail.example/x');
        SettingsService::set('notification_webhook_secret', '');

        BookingNotificationService::send(BookingNotificationService::EVENT_CANCELLED, $this->fakeBooking());
        Http::assertSentCount(2);
    }

    public function test_4xx_does_not_retry(): void
    {
        Http::fake(['https://hook.bad.example/*' => Http::response('nope', 400)]);
        SettingsService::set('notification_webhook', 'https://hook.bad.example/x');
        BookingNotificationService::send(BookingNotificationService::EVENT_COMPLETED, $this->fakeBooking());
        Http::assertSentCount(1);
    }

    public function test_connection_error_is_swallowed(): void
    {
        Http::fake();
        Http::fake(function () { throw new \Illuminate\Http\Client\ConnectionException('refused'); });
        SettingsService::set('notification_webhook', 'https://hook.dead.example/x');

        // Must not propagate
        BookingNotificationService::send(BookingNotificationService::EVENT_CREATED, $this->fakeBooking());
        $this->assertTrue(true);
    }
}
