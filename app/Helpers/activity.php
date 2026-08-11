<?php

use Illuminate\Support\Facades\DB;

if (! function_exists('log_activity')) {
    function log_activity(string $action, ?string $description = null): void
    {
        DB::table('user_activity_logs')->insert([
            'user_id' => auth()->id(),
            'action' => $action,
            'description' => $description,
            'created_at' => now(),
        ]);
    }
}
