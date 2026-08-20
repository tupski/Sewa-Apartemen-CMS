<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * BUG-002 FIX: Proteksi route installer dari akses tidak sah.
 *
 * Installer hanya bisa diakses dari:
 *  1. Localhost / loopback (127.0.0.1, ::1) — development
 *  2. IP yang ada di INSTALLER_ALLOWED_IPS (.env, comma-separated)
 *  3. Request yang membawa INSTALLER_TOKEN yang benar (.env)
 *
 * Jika tidak ada konfigurasi sama sekali (INSTALLER_ALLOWED_IPS dan
 * INSTALLER_TOKEN keduanya kosong), hanya localhost yang diizinkan.
 * Ini melindungi server production dari akses ulang installer jika
 * file installed.lock terhapus secara tidak sengaja.
 */
class ProtectInstaller
{
    public function handle(Request $request, Closure $next): Response
    {
        $clientIp    = $request->ip();
        $allowedIps  = array_filter(array_map('trim', explode(',', env('INSTALLER_ALLOWED_IPS', ''))));
        $tokenEnv    = env('INSTALLER_TOKEN', '');
        $tokenHeader = $request->header('X-Installer-Token', '');
        $tokenQuery  = $request->query('installer_token', '');

        // 1. Localhost selalu diizinkan
        if (in_array($clientIp, ['127.0.0.1', '::1', 'localhost'], true)) {
            return $next($request);
        }

        // 2. IP whitelist
        if (!empty($allowedIps) && in_array($clientIp, $allowedIps, true)) {
            return $next($request);
        }

        // 3. Token secret (header atau query param)
        if (!empty($tokenEnv)) {
            $provided = !empty($tokenHeader) ? $tokenHeader : $tokenQuery;
            if (hash_equals($tokenEnv, $provided)) {
                return $next($request);
            }
        }

        // Akses ditolak — kembalikan 403 tanpa informasi lebih lanjut
        abort(403, 'Installer access is restricted.');
    }
}
