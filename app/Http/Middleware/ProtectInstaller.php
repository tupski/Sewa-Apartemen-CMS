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
 *  2. IP yang ada di installer.allowed_ips (comma-separated)
 *  3. Request yang membawa installer.token yang benar
 *
 * Jika tidak ada konfigurasi sama sekali (allowed_ips dan token keduanya
 * kosong), hanya localhost yang diizinkan — fail-closed.
 * Ini melindungi server production dari akses ulang installer jika
 * file installed.lock terhapus secara tidak sengaja.
 *
 * SEC-11: nilai dibaca via config() (config/installer.php) bukan env(),
 * agar tetap benar ketika konfigurasi di-cache di production.
 */
class ProtectInstaller
{
    public function handle(Request $request, Closure $next): Response
    {
        $clientIp    = $request->ip();
        $allowedIps  = array_filter(array_map('trim', explode(',', (string) config('installer.allowed_ips', ''))));
        $tokenEnv    = (string) config('installer.token', '');
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
