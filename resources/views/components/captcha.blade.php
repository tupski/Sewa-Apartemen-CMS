{{--
    Reusable CAPTCHA widget.

    Renders the correct widget markup for the configured provider and pushes the
    provider's JS once (via @once) into the 'scripts' stack. Renders nothing when
    CAPTCHA is disabled or misconfigured (fail-safe), so forms keep working.

    Usage:  <x-captcha />
            <x-captcha action="login" />   action label used by reCAPTCHA v3

    Providers: recaptcha_v2 | recaptcha_v3 | hcaptcha | turnstile
--}}
@props(['action' => 'submit'])

@php
    use App\Services\CaptchaService;

    $captchaEnabled  = CaptchaService::enabled();
    $captchaProvider = CaptchaService::provider();
    $captchaSiteKey  = CaptchaService::siteKey();
@endphp

@if ($captchaEnabled)
    <div class="captcha-field mt-4">
        @switch($captchaProvider)
            @case('recaptcha_v2')
                <div class="g-recaptcha" data-sitekey="{{ $captchaSiteKey }}"></div>
                @break

            @case('hcaptcha')
                <div class="h-captcha" data-sitekey="{{ $captchaSiteKey }}"></div>
                @break

            @case('turnstile')
                <div class="cf-turnstile" data-sitekey="{{ $captchaSiteKey }}"></div>
                @break

            @case('recaptcha_v3')
                {{-- Invisible: token injected into a hidden field just before submit --}}
                <input type="hidden" name="captcha_token" value="">
                @break
        @endswitch

        {{-- Surface CAPTCHA validation errors inline --}}
        @error('captcha')
            <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
        @enderror
    </div>

    {{-- Load the provider script exactly once per page --}}
    @once
        @push('scripts')
            @if ($captchaProvider === 'recaptcha_v3')
                <script src="https://www.google.com/recaptcha/api.js?render={{ urlencode($captchaSiteKey) }}"></script>
                <script>
                    (function () {
                        var siteKey = @json($captchaSiteKey);
                        var action  = @json($action);

                        function refreshTokens() {
                            if (typeof grecaptcha === 'undefined') return;
                            grecaptcha.ready(function () {
                                grecaptcha.execute(siteKey, { action: action }).then(function (token) {
                                    document.querySelectorAll('input[name="captcha_token"]').forEach(function (el) {
                                        el.value = token;
                                    });
                                });
                            });
                        }

                        // Populate on load and refresh right before each submit (tokens expire ~2 min).
                        document.addEventListener('DOMContentLoaded', refreshTokens);
                        document.addEventListener('submit', function () {
                            refreshTokens();
                        }, true);
                    })();
                </script>
            @elseif ($captchaProvider === 'recaptcha_v2')
                <script src="https://www.google.com/recaptcha/api.js" async defer></script>
            @elseif ($captchaProvider === 'hcaptcha')
                <script src="https://js.hcaptcha.com/1/api.js" async defer></script>
            @elseif ($captchaProvider === 'turnstile')
                <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
            @endif
        @endpush
    @endonce
@endif
