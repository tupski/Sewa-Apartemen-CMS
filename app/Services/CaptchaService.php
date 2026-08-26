<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Central CAPTCHA helper shared by the <x-captcha> Blade component and the
 * VerifyCaptcha middleware, so widget rendering and server-side verification
 * always agree on the provider, response field name and endpoints.
 *
 * Supported providers (matches admin settings values):
 *   - recaptcha_v2  (Google reCAPTCHA v2 checkbox)
 *   - recaptcha_v3  (Google reCAPTCHA v3 invisible / score based)
 *   - hcaptcha      (hCaptcha)
 *   - turnstile     (Cloudflare Turnstile)
 *   - none          (disabled)
 *
 * Fail-safe: CAPTCHA is only *enforced* when a provider is selected AND both
 * the site key and secret key are present. If configuration is incomplete we
 * log a warning and skip enforcement so an admin can never lock themselves out.
 */
class CaptchaService
{
    /**
     * Provider slug from settings ('none' when disabled).
     */
    public static function provider(): string
    {
        return (string) SettingsService::get('captcha_provider', 'none');
    }

    public static function siteKey(): string
    {
        return (string) SettingsService::get('captcha_site_key', '');
    }

    public static function secretKey(): string
    {
        return (string) SettingsService::get('captcha_secret_key', '');
    }

    /**
     * True when a real provider is configured (not 'none').
     */
    public static function isProviderSelected(): bool
    {
        $provider = self::provider();

        return $provider !== '' && $provider !== 'none';
    }

    /**
     * True only when CAPTCHA should be enforced: a provider is selected AND
     * both keys are present. This is the fail-safe gate used by both the
     * widget (render) and the middleware (verify).
     */
    public static function enabled(): bool
    {
        if (! self::isProviderSelected()) {
            return false;
        }

        if (self::siteKey() === '' || self::secretKey() === '') {
            // Misconfigured: provider chosen but keys missing. Do NOT enforce.
            Log::warning('CAPTCHA is enabled in settings but not fully configured; skipping enforcement to avoid lockout.', [
                'provider'      => self::provider(),
                'has_site_key'  => self::siteKey() !== '',
                'has_secret'    => self::secretKey() !== '',
            ]);

            return false;
        }

        return true;
    }

    /**
     * The request field name that carries the CAPTCHA response for the
     * configured provider.
     */
    public static function responseField(): string
    {
        return match (self::provider()) {
            'recaptcha_v2' => 'g-recaptcha-response',
            'recaptcha_v3' => 'captcha_token',
            'hcaptcha'     => 'h-captcha-response',
            'turnstile'    => 'cf-turnstile-response',
            default        => 'captcha_token',
        };
    }

    /**
     * External JS URL for the configured provider (site key interpolated for v3).
     */
    public static function scriptUrl(): ?string
    {
        return match (self::provider()) {
            'recaptcha_v2' => 'https://www.google.com/recaptcha/api.js',
            'recaptcha_v3' => 'https://www.google.com/recaptcha/api.js?render=' . urlencode(self::siteKey()),
            'hcaptcha'     => 'https://js.hcaptcha.com/1/api.js',
            'turnstile'    => 'https://challenges.cloudflare.com/turnstile/v0/api.js',
            default        => null,
        };
    }

    /**
     * Verify a CAPTCHA response token. Returns true when verification passes.
     * Fails OPEN on network/transport errors so a provider outage cannot lock
     * users out.
     */
    public static function verify(string $token, ?string $ip = null): bool
    {
        $provider = self::provider();
        $secret   = self::secretKey();

        if ($secret === '') {
            return true; // fail-safe: nothing to verify against
        }

        try {
            $endpoint = match ($provider) {
                'recaptcha_v2', 'recaptcha_v3' => 'https://www.google.com/recaptcha/api/siteverify',
                'hcaptcha'                     => 'https://hcaptcha.com/siteverify',
                'turnstile'                    => 'https://challenges.cloudflare.com/turnstile/v0/siteverify',
                default                        => null,
            };

            if ($endpoint === null) {
                return true; // unknown provider: don't block
            }

            $res = Http::asForm()->timeout(5)->post($endpoint, [
                'secret'   => $secret,
                'response' => $token,
                'remoteip' => $ip ?? '',
            ])->json();

            $success = (bool) ($res['success'] ?? false);

            // reCAPTCHA v3 also enforces a minimum score.
            if ($provider === 'recaptcha_v3') {
                $minScore = (float) SettingsService::get('captcha_recaptcha_min_score', 0.5);

                return $success && (($res['score'] ?? 0) >= $minScore);
            }

            return $success;
        } catch (\Throwable $e) {
            Log::warning('CAPTCHA verification request failed; failing open.', [
                'provider' => $provider,
                'error'    => $e->getMessage(),
            ]);

            return true; // fail open on network error
        }
    }
}
