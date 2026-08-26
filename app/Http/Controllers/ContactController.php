<?php

namespace App\Http\Controllers;

use App\Services\SeoService;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ContactController extends Controller
{
    /**
     * Display the public contact page (contact info + form).
     */
    public function index()
    {
        $seo = SeoService::metaTags(
            __('contact.title') . ' - ' . SettingsService::get('site_name', config('app.name')),
            __('contact.subtitle'),
            route('contact'),
        );

        return view('contact.index', compact('seo'));
    }

    /**
     * Handle a contact form submission.
     *
     * Validates the input, applies a honeypot spam guard, then attempts to
     * email the site's contact address using the configured mailer. If mail
     * delivery fails (or no contact email is configured) the message is logged
     * so it is never silently lost, and the user still receives a success flash.
     */
    public function store(Request $request)
    {
        // Honeypot: bots fill hidden fields humans never see. Silently succeed.
        if (filled($request->input('website'))) {
            return redirect()->route('contact')->with('success', __('contact.success'));
        }

        $validated = $request->validate([
            'name'    => ['required', 'string', 'max:120'],
            'email'   => ['required', 'email', 'max:190'],
            'subject' => ['required', 'string', 'max:190'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $contactEmail = trim((string) SettingsService::get('contact_email', ''));
        $siteName = SettingsService::get('site_name', config('app.name', 'Kakarama Room'));

        $body = "Name: {$validated['name']}\n"
            . "Email: {$validated['email']}\n"
            . "Subject: {$validated['subject']}\n\n"
            . $validated['message'];

        try {
            if ($contactEmail !== '') {
                Mail::raw($body, function ($mail) use ($contactEmail, $validated, $siteName) {
                    $mail->to($contactEmail)
                        ->subject('[' . $siteName . '] ' . Str::limit($validated['subject'], 120))
                        ->replyTo($validated['email'], $validated['name']);
                });
            } else {
                // No contact email configured — log so the message is not lost.
                Log::info('contact.form.submitted', $validated);
            }
        } catch (\Throwable $e) {
            // Mail delivery must never break the UX; log and continue.
            Log::warning('contact.form.mail_failed', [
                'error' => $e->getMessage(),
                'payload' => $validated,
            ]);
        }

        return redirect()->route('contact')->with('success', __('contact.success'));
    }
}
