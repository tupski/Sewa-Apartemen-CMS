/**
 * Shared HTML-escaping helper.
 *
 * Emits NUMERIC character references (`&#60;`) rather than named entities
 * (`&lt;`) — both are valid HTML, and numeric refs avoid depending on the
 * entity table. Every value interpolated into a template string must pass
 * through here; the map popups, the search autocomplete highlight and the POI
 * marker icons all render server- or user-supplied text into `innerHTML`.
 */

/**
 * Escape a value for HTML text/attribute context.
 *
 * Coerces non-strings first, so a numeric or null value cannot throw.
 *
 * @param {unknown} value
 * @returns {string}
 */
export function escapeHtml(value) {
    return String(value).replace(/[&<>"']/g, (c) => '&#' + c.charCodeAt(0) + ';');
}
