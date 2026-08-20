<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

if (! function_exists('log_activity')) {
    /**
     * Catat aktivitas user ke tabel user_activity_logs.
     *
     * BUG-009 FIX:
     *  1. Guard null user_id — tidak crash jika dipanggil dari konteks unauthenticated
     *     (queue, Artisan command, installer). Log ke Laravel log sebagai fallback.
     *  2. Tambah updated_at agar kompatibel dengan tabel yang punya timestamps().
     *  3. Bungkus dalam try/catch — kegagalan log tidak boleh menghentikan proses utama.
     */
    function log_activity(string $action, ?string $description = null): void
    {
        try {
            $userId = auth()->id();

            if ($userId === null) {
                // Konteks unauthenticated — tulis ke Laravel log saja, jangan crash
                Log::info("[activity] {$action}", ['description' => $description]);
                return;
            }

            DB::table('user_activity_logs')->insert([
                'user_id'     => $userId,
                'action'      => $action,
                'description' => $description,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        } catch (\Throwable $e) {
            // Gagal log tidak boleh mengganggu flow utama
            Log::warning("[activity] Failed to log activity '{$action}': " . $e->getMessage());
        }
    }
}
