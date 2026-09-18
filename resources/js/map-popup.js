/**
 * Leaflet popup HTML for the property page map.
 *
 * Kept dependency-free and pure so the string composition can be unit-tested:
 * a doubled unit ("813m Km") is a pure formatting bug that no PHP test can
 * catch, and it shipped once because the logic lived inside a DOM-bound
 * initialiser.
 */

/**
 * Escape a value for HTML text/attribute context.
 *
 * @param {unknown} value
 * @returns {string}
 */
export function escapeHtml(value) {
    return String(value).replace(/[&<>"']/g, (c) => '&#' + c.charCodeAt(0) + ';');
}

/** Icon per travel mode, mirroring the on-page travel chips. */
export const MODE_ICONS = {
    walking: 'fa-person-walking',
    driving: 'fa-car-side',
    motorcycle: 'fa-motorcycle'
};

const MUTED = 'color:#6b7280;font-size:0.75rem';

/**
 * "<icon> 15 min" for one travel mode; '' when unmeasured.
 *
 * @param {string} mode
 * @param {unknown} minutes
 * @param {string} minutesLabel
 * @returns {string}
 */
function travelChip(mode, minutes, minutesLabel) {
    if (minutes === null || minutes === undefined || String(minutes) === '') {
        return '';
    }

    return '<i class="fa-solid ' + MODE_ICONS[mode] + '" style="margin-right:3px" aria-hidden="true"></i>'
        + escapeHtml(minutes) + ' ' + escapeHtml(minutesLabel);
}

/**
 * The property's own pin: "<name> by <site name>" as the title, then the
 * cheapest rate, the booking phone and a directions button.
 *
 * @param {object} m
 * @param {object} labels
 * @returns {string}
 */
export function buildPropertyPopup(m, labels = {}) {
    const siteName = String(m.site_name || '');
    // "by" is intentional fixed copy (not a translated connector).
    const title = escapeHtml(String(m.name || ''))
        + (siteName ? ' <span style="font-weight:400">by ' + escapeHtml(siteName) + '</span>' : '');

    let popup = '<strong>' + title + '</strong>';

    const price = Number(m.price_from);
    if (m.price_from !== null && m.price_from !== undefined && !isNaN(price) && price > 0) {
        popup += '<br><span style="' + MUTED + '">'
            + escapeHtml(String(labels.price_from || 'From')) + ' Rp '
            + escapeHtml(price.toLocaleString('id-ID')) + '</span>';
    }

    const phone = String(m.booking_phone || '');
    if (phone) {
        popup += '<br><span style="' + MUTED + '">'
            + escapeHtml(String(labels.booking || 'Booking')) + ': '
            + escapeHtml(phone) + '</span>';
    }

    // Only http(s) — never render an arbitrary scheme from the payload.
    if (m.directions_url && /^https?:\/\//i.test(String(m.directions_url))) {
        popup += '<br><a href="' + escapeHtml(String(m.directions_url)) + '" target="_blank" rel="noopener noreferrer"'
            + ' style="display:inline-flex;align-items:center;gap:4px;margin-top:6px;color:#2563eb;font-size:0.75rem;font-weight:600">'
            + '<i class="fa-solid fa-diamond-turn-right" aria-hidden="true"></i>'
            + escapeHtml(String(labels.directions || 'Directions')) + '</a>';
    }

    return popup;
}

/**
 * A nearby POI, in this order: name, category, "<distance> from <property>",
 * address — then the measured travel times (each paired with a mode icon).
 *
 * @param {object} m
 * @param {object} labels
 * @returns {string}
 */
export function buildPoiPopup(m, labels = {}) {
    let popup = '<strong>' + escapeHtml(String(m.name || '')) + '</strong>';

    if (m.cat_label) {
        popup += '<br><span style="' + MUTED + '">' + escapeHtml(String(m.cat_label)) + '</span>';
    }

    // The payload ships an already-formatted distance ("813m", "1.2km"), so the
    // label template must NOT append a unit — that produced "813m Km".
    const distance = String(m.distance || '');
    const from = String(m.distance_from || '');
    if (distance) {
        const distanceLine = from
            ? String(labels.distance_from || ':distance from :name')
                .replace(':distance', distance)
                .replace(':name', from)
            : distance;
        popup += '<br><span style="' + MUTED + '">' + escapeHtml(distanceLine) + '</span>';
    }

    if (m.address) {
        popup += '<br><span style="' + MUTED + ';display:inline-flex;align-items:flex-start;gap:4px">'
            + '<i class="fa-solid fa-location-dot" style="margin-top:2px" aria-hidden="true"></i>'
            + '<span>' + escapeHtml(String(m.address)) + '</span></span>';
    }

    // One icon per measured mode; unmeasured modes are omitted entirely.
    const minutesLabel = typeof labels.minutes === 'string' && labels.minutes ? labels.minutes : 'min';
    const travel = [
        travelChip('walking', m.walking, minutesLabel),
        travelChip('driving', m.driving, minutesLabel),
        travelChip('motorcycle', m.motorcycle, minutesLabel)
    ].filter(Boolean).join(' <span style="color:#d1d5db">\u00b7</span> ');

    if (travel) {
        popup += '<br><span style="' + MUTED + ';display:inline-flex;align-items:center;flex-wrap:wrap;gap:2px">'
            + travel + '</span>';
    }

    // Only http(s) links — never render an arbitrary scheme from the payload.
    if (m.website && /^https?:\/\//i.test(String(m.website))) {
        popup += '<br><a href="' + escapeHtml(String(m.website)) + '" target="_blank" rel="noopener noreferrer" style="color:#2563eb;font-size:0.75rem">'
            + escapeHtml(String(m.website)) + '</a>';
    }
    if (m.phone) {
        popup += '<br><span style="' + MUTED + '">' + escapeHtml(String(m.phone)) + '</span>';
    }

    return popup;
}
