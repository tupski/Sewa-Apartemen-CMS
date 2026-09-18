import test from 'node:test';
import assert from 'node:assert/strict';

import { buildPropertyPopup, buildPoiPopup } from './map-popup.js';

const LABELS = {
    distance_from: ':distance from :name',
    minutes: 'min',
    price_from: 'From',
    booking: 'Booking',
    directions: 'Get Directions'
};

/* ======================================================================
 | POI popup — order and the doubled-unit bug
 * ==================================================================== */

test('poi popup does not double the distance unit', () => {
    const html = buildPoiPopup(
        { name: 'Cafe', distance: '813m', distance_from: 'Springwood' },
        LABELS
    );

    assert.ok(html.includes('813m from Springwood'), html);
    // The regression: the template appended its own "Km" to an already
    // formatted value, rendering "813m Km from ...".
    assert.ok(!/813m\s*(km|Km)/.test(html), 'doubled unit: ' + html);
    assert.ok(!html.includes('813m Km'), html);
});

test('poi popup orders name, category, distance, address', () => {
    const html = buildPoiPopup(
        {
            name: 'Grand Mall',
            cat_label: 'Shopping Mall',
            distance: '900m',
            distance_from: 'Springwood',
            address: 'Jl. Raya Serpong'
        },
        LABELS
    );

    const at = (needle) => html.indexOf(needle);
    assert.ok(at('Grand Mall') < at('Shopping Mall'), html);
    assert.ok(at('Shopping Mall') < at('900m from Springwood'), html);
    assert.ok(at('900m from Springwood') < at('Jl. Raya Serpong'), html);
});

test('poi popup falls back to a bare distance when no source is known', () => {
    const html = buildPoiPopup({ name: 'Cafe', distance: '813m' }, LABELS);
    assert.ok(html.includes('813m'), html);
    assert.ok(!html.includes('from'), html);
});

test('poi popup omits unmeasured travel modes', () => {
    const html = buildPoiPopup(
        { name: 'Cafe', distance: '813m', walking: '15', driving: null, motorcycle: '' },
        LABELS
    );

    assert.ok(html.includes('fa-person-walking'), html);
    assert.ok(!html.includes('fa-car-side'), html);
    assert.ok(!html.includes('fa-motorcycle'), html);
});

/* ======================================================================
 | Property popup — "by <site name>" in the title
 * ==================================================================== */

test('property popup puts the site name in the title with a literal "by"', () => {
    const html = buildPropertyPopup(
        { name: 'Springwood Residence', site_name: 'Sewa Apartemen' },
        LABELS
    );

    assert.ok(html.startsWith('<strong>Springwood Residence <span'), html);
    assert.ok(html.includes('by Sewa Apartemen</span></strong>'), html);
    // Hardcoded English connector — never translated to "oleh".
    assert.ok(!html.includes('oleh'), html);
});

test('property popup omits the connector when no site name is set', () => {
    const html = buildPropertyPopup({ name: 'Springwood Residence' }, LABELS);
    assert.ok(html.startsWith('<strong>Springwood Residence</strong>'), html);
    assert.ok(!html.includes('by '), html);
});

test('property popup renders price, phone and a directions link', () => {
    const html = buildPropertyPopup(
        {
            name: 'Springwood',
            site_name: 'Site',
            price_from: 250000,
            booking_phone: '+628123',
            directions_url: 'https://www.google.com/maps/dir/?api=1&destination=-6.2,106.8'
        },
        LABELS
    );

    assert.ok(html.includes('From Rp'), html);
    assert.ok(html.includes('250.000'), html);
    assert.ok(html.includes('Booking: +628123'), html);
    assert.ok(html.includes('fa-diamond-turn-right'), html);
    assert.ok(html.includes('Get Directions'), html);
});

test('property popup refuses a non-http directions URL', () => {
    const html = buildPropertyPopup(
        { name: 'X', directions_url: 'javascript:alert(1)' },
        LABELS
    );

    assert.ok(!html.includes('javascript:'), html);
    assert.ok(!html.includes('fa-diamond-turn-right'), html);
});

/* ======================================================================
 | Escaping
 * ==================================================================== */

test('popups escape markup from the payload', () => {
    const html = buildPoiPopup(
        { name: '<img src=x onerror=alert(1)>', distance: '1km', address: '"><script>' },
        LABELS
    );

    // escapeHtml emits NUMERIC char refs (&#60;), not named entities (&lt;),
    // so assert on the absence of live markup and on the escaped form.
    assert.ok(!html.includes('<img'), html);
    assert.ok(!html.includes('<script>'), html);
    assert.ok(html.includes('&#60;img'), html);
});
