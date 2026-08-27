<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Installer Access Control
    |--------------------------------------------------------------------------
    |
    | SEC-11: these values are resolved here at config-load time so they keep
    | working when the configuration is cached (`php artisan config:cache`).
    | Reading them with env() at runtime inside middleware returns null once
    | the config cache is warm, which silently alters the access decision.
    |
    | allowed_ips : comma-separated list of IPs allowed to reach the installer.
    | token       : shared secret accepted via the X-Installer-Token header or
    |               the installer_token query parameter.
    |
    */

    'allowed_ips' => env('INSTALLER_ALLOWED_IPS', ''),

    'token' => env('INSTALLER_TOKEN', ''),

];
