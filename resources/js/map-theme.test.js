import test from 'node:test';
import assert from 'node:assert/strict';

import { resolveMapTileUrl, isGeoapifyTileUrl } from './map-theme.js';

const LIGHT = 'https://maps.geoapify.com/v1/tile/positron/{z}/{x}/{y}.png?apiKey=k';
const DARK = 'https://maps.geoapify.com/v1/tile/dark-matter/{z}/{x}/{y}.png?apiKey=k';
const FALLBACK = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';

test('follow mode tracks the live theme', () => {
    assert.equal(resolveMapTileUrl({ themeMode: 'follow', isDark: true, lightUrl: LIGHT, darkUrl: DARK }), DARK);
    assert.equal(resolveMapTileUrl({ themeMode: 'follow', isDark: false, lightUrl: LIGHT, darkUrl: DARK }), LIGHT);
});

test('a pinned light mode ignores the site dark theme', () => {
    assert.equal(resolveMapTileUrl({ themeMode: 'light', isDark: true, lightUrl: LIGHT, darkUrl: DARK }), LIGHT);
});

test('a pinned dark mode ignores the site light theme', () => {
    assert.equal(resolveMapTileUrl({ themeMode: 'dark', isDark: false, lightUrl: LIGHT, darkUrl: DARK }), DARK);
});

test('an unrecognised mode behaves like follow', () => {
    assert.equal(resolveMapTileUrl({ themeMode: 'nonsense', isDark: true, lightUrl: LIGHT, darkUrl: DARK }), DARK);
});

test('a missing variant falls back', () => {
    assert.equal(
        resolveMapTileUrl({ themeMode: 'follow', isDark: true, lightUrl: LIGHT, darkUrl: '', fallback: FALLBACK }),
        FALLBACK
    );
    assert.equal(resolveMapTileUrl({}), null);
});

test('geoapify detection', () => {
    assert.equal(isGeoapifyTileUrl(LIGHT), true);
    assert.equal(isGeoapifyTileUrl(FALLBACK), false);
    assert.equal(isGeoapifyTileUrl(null), false);
    assert.equal(isGeoapifyTileUrl(undefined), false);
    assert.equal(isGeoapifyTileUrl(42), false);
});
