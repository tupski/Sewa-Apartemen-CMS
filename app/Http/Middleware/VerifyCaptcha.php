<?php

namespace App\Http\Middleware;

use App\Services\CaptchaService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verify CAPTCHA token for guarded forms (login / forgot-password / etc.).
 *
 * Delegates provider handling and fail-safe logic to CaptchaService:
 *   - Skips entirely when CAPTCHA is disabled OR misconfigured (missing keys),
 *     so an admin can never lock themselves out.
 *   - Reads the correct response field for the configured provider.
 *   - Fails open on provider network errors.
 */
class VerifyCaptcha
{
    public function handle(Request $request, Closure $next): Response
    {
        // Fail-safe gate: only enforce when a provider is selected AND both keys exist.
        if (! CaptchaService::enabled()) {
            return $next($request);
        }

        // Pull the token from the provider-specific field (with fallbacks).
        $field = CaptchaService::responseField();
        $token = (string) $request->input($field, $request->input('captcha_token', ''));

        if ($token === '') {
            return back()
                ->withErrors(['captcha' => 'Verifikasi CAPTCHA diperlukan.'])
                ->withInput($request->except('password'));
        }

        if (! CaptchaService::verify($token, $request->ip())) {
            return back()
                ->withErrors(['captcha' => 'Verifikasi CAPTCHA gagal. Silakan coba lagi.'])
                ->withInput($request->except('password'));
        }

        return $next($request);
    }
}
