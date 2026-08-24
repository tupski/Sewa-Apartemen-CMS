<?php

namespace App\Services;

/**
 * Allowlist HTML sanitizer for admin-entered rich content (FIND-005).
 *
 * Strips <script>, event handlers, javascript:/data:/vbscript: URLs and any
 * tag/attribute outside the allowed set. Uses ext-dom (stdlib); no extra deps.
 */
class SafeHtmlService
{
    /** Tags preserved (formatting + layout). Everything else is unwrapped. */
    protected const ALLOWED_TAGS = [
        'p', 'br', 'strong', 'em', 'b', 'i', 'u', 's', 'ul', 'ol', 'li',
        'a', 'img', 'blockquote', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'pre', 'code', 'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td',
        'span', 'div', 'hr', 'figure', 'figcaption', 'caption', 'sub', 'sup',
    ];

    /** Attributes preserved on allowed tags. */
    protected const ALLOWED_ATTRS = [
        'href', 'title', 'alt', 'src', 'width', 'height', 'target', 'rel',
        'class', 'id', 'style', 'colspan', 'rowspan', 'align', 'start', 'type',
    ];

    /** URL scheme whitelist for navigation/media attributes. */
    protected const SAFE_SCHEMES = ['http', 'https', 'mailto', 'tel'];

    /**
     * Sanitize an HTML fragment, returning safe HTML (or null for empty input).
     */
    public static function sanitize(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return $html;
        }

        $doc = new \DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $loaded = $doc->loadHTML(
            '<?xml encoding="UTF-8"><div id="__safehtml">' . $html . '</div>',
            LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (!$loaded) {
            return strip_tags($html);
        }

        $root = $doc->getElementById('__safehtml');
        if (!$root) {
            return strip_tags($html);
        }

        static::stripDisallowedTags($root);
        static::sanitizeAttributes($root);

        $out = '';
        foreach ($root->childNodes as $node) {
            $out .= $doc->saveHTML($node);
        }

        return $out;
    }

    /**
     * Remove disallowed tags, keeping their children (unwrap).
     */
    protected static function stripDisallowedTags(\DOMElement $root): void
    {
        $removed = true;
        while ($removed) {
            $removed = false;
            foreach ($root->getElementsByTagName('*') as $el) {
                if (!in_array(strtolower($el->tagName), self::ALLOWED_TAGS, true)) {
                    $parent = $el->parentNode;
                    while ($el->firstChild) {
                        $parent->insertBefore($el->firstChild, $el);
                    }
                    $parent->removeChild($el);
                    $removed = true;
                    break;
                }
            }
        }
    }

    /**
     * Drop dangerous/unknown attributes on the remaining elements.
     */
    protected static function sanitizeAttributes(\DOMElement $root): void
    {
        foreach ($root->getElementsByTagName('*') as $el) {
            $drop = [];

            foreach ($el->attributes as $attr) {
                $name = strtolower($attr->name);
                $value = trim((string) $attr->value);

                if ($name === 'style') {
                    if (preg_match('/expression\s*\(|javascript\s*:|@import|url\s*\(\s*["\']?\s*vbscript|url\s*\(\s*["\']?\s*javascript/i', $value)) {
                        $drop[] = $attr->name;
                    }
                    continue;
                }

                if (in_array($name, ['href', 'src', 'xlink:href', 'action', 'formaction'], true)) {
                    if (preg_match('/^\s*([a-z][a-z0-9+.-]*)\s*:/i', $value, $m)
                        && !in_array(strtolower($m[1]), self::SAFE_SCHEMES, true)) {
                        $drop[] = $attr->name;
                        continue;
                    }
                }

                if (str_starts_with($name, 'on')) {
                    $drop[] = $attr->name;
                    continue;
                }

                if (!in_array($name, self::ALLOWED_ATTRS, true)) {
                    $drop[] = $attr->name;
                }
            }

            foreach ($drop as $name) {
                $el->removeAttribute($name);
            }
        }
    }
}
