<?php

namespace App\Services;

/**
 * Safe renderer for the `contact_map_embed` setting (SEC-04).
 *
 * The setting historically stored a raw `<iframe>` blob copied out of Google
 * Maps' "Share → Embed a map" dialog, and the contact page rendered it with
 * `{!! !!}` — a stored-XSS sink for anyone able to write settings (DB access,
 * seeder, or a restored backup).
 *
 * This service never echoes stored markup. It extracts a *URL* from the stored
 * value (accepting either a bare URL or a legacy iframe blob), validates that
 * URL against a Google Maps allowlist, and then builds a brand-new iframe
 * server-side with escaped attributes. Anything that fails validation yields
 * null, so the caller renders nothing.
 *
 * `SafeHtmlService` is deliberately not reused here: it has no `iframe` in its
 * allowed-tag list, so it would strip the map entirely, and widening it to
 * permit iframes would weaken sanitization everywhere else it is used.
 */
class MapEmbedService
{
    /**
     * Hosts permitted to serve the map. Matched case-insensitively against the
     * full host, after stripping a trailing dot.
     *
     * `google.<tld>` is matched by pattern to cover regional domains
     * (google.co.id, google.de, …) that the Maps embed dialog emits.
     */
    protected const HOST_PATTERN = '/^(?:www\.|maps\.)?google(?:\.[a-z]{2,3}){1,2}$/i';

    /** The URL path must begin with one of these prefixes. */
    protected const ALLOWED_PATH_PREFIXES = ['/maps'];

    /**
     * Extract and validate the map URL from a stored setting value.
     *
     * Accepts either a bare `https://www.google.com/maps/embed?...` URL or a
     * legacy `<iframe src="...">` blob.
     *
     * @return string|null the validated URL, or null when the value is unusable
     */
    public static function url(?string $stored): ?string
    {
        $value = trim((string) $stored);

        if ($value === '') {
            return null;
        }

        // Reject control characters outright — they only appear in evasion
        // attempts (e.g. "java\0script:") and never in a legitimate embed.
        if (preg_match('/[\x00-\x1F\x7F]/', $value)) {
            return null;
        }

        $url = str_starts_with(strtolower($value), '<')
            ? static::srcFromIframe($value)
            : $value;

        if ($url === null) {
            return null;
        }

        return static::validateUrl($url);
    }

    /**
     * Build a safe `<iframe>` for the stored setting value.
     *
     * @return string|null ready-to-render HTML, or null when nothing should render
     */
    public static function iframe(?string $stored, string $title = 'Map'): ?string
    {
        $url = static::url($stored);

        if ($url === null) {
            return null;
        }

        return sprintf(
            '<iframe src="%s" title="%s" class="w-full h-full" width="100%%" height="100%%"'
            . ' style="border:0" loading="lazy" referrerpolicy="no-referrer-when-downgrade"'
            . ' allowfullscreen></iframe>',
            htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
        );
    }

    /**
     * Pull the `src` out of a legacy iframe blob.
     *
     * The blob must be a single `<iframe>` element and must not contain any
     * other tag (notably `<script>`) or any event-handler attribute. The `src`
     * itself is validated separately by {@see validateUrl()}.
     */
    protected static function srcFromIframe(string $html): ?string
    {
        // Exactly one tag, and it must be an iframe (open + optional close).
        if (preg_match_all('/<\s*\/?\s*([a-z0-9:_-]+)/i', $html, $tags) === false) {
            return null;
        }

        foreach ($tags[1] as $tag) {
            if (strtolower($tag) !== 'iframe') {
                return null;
            }
        }

        if (count($tags[1]) === 0 || count($tags[1]) > 2) {
            return null;
        }

        // Any event-handler attribute disqualifies the whole value.
        if (preg_match('/\son[a-z-]+\s*=/i', $html)) {
            return null;
        }

        // srcdoc would let an attacker inline a whole document.
        if (preg_match('/\ssrcdoc\s*=/i', $html)) {
            return null;
        }

        if (! preg_match('/\ssrc\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>]+))/i', $html, $m)) {
            return null;
        }

        $src = $m[1] !== '' ? $m[1] : ($m[2] ?? '');
        if ($src === '') {
            $src = $m[3] ?? '';
        }

        $src = trim(html_entity_decode($src, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return $src !== '' ? $src : null;
    }

    /**
     * Enforce https + Google Maps host + /maps path.
     */
    protected static function validateUrl(string $url): ?string
    {
        if (preg_match('/\s/', $url)) {
            return null;
        }

        $parts = parse_url($url);

        if ($parts === false || ! is_array($parts)) {
            return null;
        }

        if (strtolower($parts['scheme'] ?? '') !== 'https') {
            return null;
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }

        $host = rtrim(strtolower($parts['host'] ?? ''), '.');

        if ($host === '' || ! preg_match(self::HOST_PATTERN, $host)) {
            return null;
        }

        $path = $parts['path'] ?? '';
        $pathAllowed = false;
        foreach (self::ALLOWED_PATH_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                $pathAllowed = true;
                break;
            }
        }

        if (! $pathAllowed) {
            return null;
        }

        return $url;
    }
}
