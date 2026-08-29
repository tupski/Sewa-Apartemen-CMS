<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Artivo CMS Version (SINGLE SOURCE OF TRUTH)
    |--------------------------------------------------------------------------
    |
    | The canonical SemVer (MAJOR.MINOR.PATCH) version of the Artivo CMS
    | platform powering this site. This literal is the ONLY place the version
    | number is written — read it everywhere else with `config('artivo.version')`.
    |
    | Bump it here, and add a matching dated entry to CHANGELOG.md, whenever a
    | release is cut. It is intentionally a plain literal (not `env()`) so the
    | value is always available: it survives `php artisan config:cache`, needs
    | no `.env` key, and never requires shelling out to git.
    |
    */

    'version' => '1.0.0',

    /*
    |--------------------------------------------------------------------------
    | Product Identity
    |--------------------------------------------------------------------------
    |
    | Product name, vendor, and the public product URL used by the
    | "Powered by Artivo CMS" credit rendered in the public and admin footers.
    | The product name is a brand and is deliberately NOT translated.
    |
    */

    'product' => 'Artivo CMS',

    'vendor' => 'PT KAKARAMA Samudera Group',

    'url' => 'https://artivo.artupski.com',

];
