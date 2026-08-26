<?php

namespace App\Http\Middleware;

use App\Services\SettingsService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verify CAPTCHA token for login / forgot-password.
 * Supports: Google reCAPTCHA v3, Cloudflare Turnstile.
 * Skips silently when CAPTCHA is disabled (no secret key set).
 */
class VerifyCaptcha
{
    public function handle(Request $request, Closure $next): Response
    {
        $provider = SettingsService::get('captcha_provider', 'none'); // 'recaptcha' | 'turnstile' | 'none'
        $secret   = SettingsService::get('captcha_secret_key', '');

        if ($provider === 'none' || empty($secret)) {
            return $next($request);
        }

        $token = $request->input('captcha_token', $request->input('cf-turnstile-response', ''));

        if (empty($token)) {
            return back()->withErrors(['captcha' => 'Verifikasi CAPTCHA diperlukan.'])->withInput();
        }

        $valid = match ($provider) {
            'recaptcha'  => $this->verifyRecaptcha($token, $secret, $request->ip()),
            'turnstile'  => $this->verifyTurnstile($token, $secret, $request->ip()),
            default      => true,
        };

        if (!$valid) {
            return back()->withErrors(['captcha' => 'Verifikasi CAPTCHA gagal. Silakan coba lagi.'])->withInput();
        }

        return $next($request);
    }

    private function verifyRecaptcha(string $token, string $secret, string $ip): bool
    {
        $minScore = (float) SettingsService::get('captcha_recaptcha_min_score', 0.5);
        try {
            $res = Http::asForm()->timeout(5)->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret'   => $secret,
                'response' => $token,
                'remoteip' => $ip,
            ])->json();
            return ($res['success'] ?? false) && (($res['score'] ?? 0) >= $minScore);
        } catch (\Throwable $e) {
            return true; // fail open on network error
        }
    }

    private function verifyTurnstile(string $token, string $secret, string $ip): bool
    {
        try {
            $res = Http::asForm()->timeout(5)->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret'   => $secret,
                'response' => $token,
                'remoteip' => $ip,
            ])->json();
            return (bool) ($res['success'] ?? false);
        } catch (\Throwable $e) {
            return true; // fail open on network error
        }
    }
}
