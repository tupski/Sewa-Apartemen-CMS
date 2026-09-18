/**
 * Map tile resolution for the public property page.
 *
 * The server resolves a tile style for the theme it *believes* is active, by
 * reading the `theme` cookie. That belief can be stale: the in-page theme
 * toggle flips the `dark` class on <html> without a reload, so the cookie —
 * and therefore the next server render — still describes the previous theme.
 * A Turbo Drive navigation then reuses the already-dark document while the
 * freshly rendered payload may still carry the light style, which painted a
 * LIGHT map on a DARK page. Deriving the tile URL from the LIVE theme is what
 * keeps the map in step with the page.
 *
 * A pinned map theme mode ('light' / 'dark') deliberately ignores the site
 * theme, so it must NOT be resolved from the `dark` class.
 */

/**
 * Pick the tile URL for a given theme state.
 *
 * @param {object} options
 * @param {string} options.themeMode  'follow' | 'light' | 'dark'
 * @param {boolean} options.isDark    whether the document is in dark mode
 * @param {string} [options.lightUrl] resolved light tile URL
 * @param {string} [options.darkUrl]  resolved dark tile URL
 * @param {string} [options.fallback] URL used when the chosen one is missing
 * @returns {string|null}
 */
export function resolveMapTileUrl({ themeMode, isDark, lightUrl, darkUrl, fallback } = {}) {
    if (themeMode === 'light') {
        return lightUrl || fallback || null;
    }
    if (themeMode === 'dark') {
        return darkUrl || fallback || null;
    }

    // 'follow' (and any unrecognised value): track the live site theme.
    return (isDark ? darkUrl : lightUrl) || fallback || null;
}

/**
 * Whether a tile URL is served by Geoapify — it needs the map key and supports
 * a deeper zoom than the keyless OpenStreetMap fallback.
 *
 * @param {unknown} url
 * @returns {boolean}
 */
export function isGeoapifyTileUrl(url) {
    return typeof url === 'string' && url.indexOf('geoapify.com') !== -1;
}
