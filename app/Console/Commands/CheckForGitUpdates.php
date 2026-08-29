<?php

namespace App\Console\Commands;

use App\Services\GitService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * CheckForGitUpdates
 *
 * Checks whether the deployed codebase is behind its git remote and persists
 * the result to the application cache so the admin header can read it cheaply
 * on every page render without ever touching git or the network (AGENTS.md §14).
 *
 * Scheduled daily at 01:00 WIB (Asia/Jakarta) — timezone is pinned explicitly
 * in routes/console.php because app timezone is UTC.
 *
 * Store choice — file-driver Cache (key: git_update_check):
 *   • Survives between requests with zero DB overhead.
 *   • Follows the same pattern used by CurrencyRateService (file cache).
 *   • The Setting model is for user-configurable values, not ephemeral
 *     operational state produced by the scheduler.
 *   • Cache reads are O(1) from the filesystem — safe on every admin page load.
 */
class CheckForGitUpdates extends Command
{
    /**
     * Cache key used everywhere this state is read or written.
     * Never duplicated as a magic string — always reference this constant.
     */
    public const CACHE_KEY = 'git_update_check';

    protected $signature = 'git:check-updates
                            {--force : Run even if a recent check result is cached}';

    protected $description = 'Check whether the deployed code is behind its git remote and cache the result (runs daily at 01:00 WIB via scheduler)';

    public function __construct(protected GitService $gitService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $cwd = base_path();

        // Allow skipping repeated checks within the same day unless --force is
        // passed.  This makes the command safe to run repeatedly (idempotent)
        // and avoids hammering the remote on manual invocations.
        if (! $this->option('force') && Cache::has(self::CACHE_KEY)) {
            $cached = Cache::get(self::CACHE_KEY);
            $this->line('<info>Skipped — cached result from '.($cached['checked_at'] ?? 'unknown').'.</info>');
            $this->line('<comment>Pass --force to override.</comment>');

            return self::SUCCESS;
        }

        $this->line('Checking for git updates…');

        try {
            $result = $this->gitService->checkForUpdates($cwd);
        } catch (\Throwable $e) {
            // Redact any credential that might be embedded in a remote URL
            // before writing to the log (AGENTS.md §15, SEC-13).
            $safe = $this->gitService->redactRemoteUrl($e->getMessage());
            Log::warning('git:check-updates — unexpected exception: '.$safe);

            // Persist a "check ran but failed" state so the header does NOT
            // show a spurious update badge while the error is unresolved.
            Cache::forever(self::CACHE_KEY, [
                'available' => false,
                'commits_behind' => 0,
                'commits_ahead' => 0,
                'remote_sha' => '',
                'remote_subject' => '',
                'remote_author' => '',
                'remote_date' => '',
                'checked_at' => now()->toIso8601String(),
                'error' => $safe,
                'skipped_reason' => 'exception',
            ]);

            $this->line('<error>Failed: '.$safe.'</error>');

            return self::FAILURE;
        }

        // Log non-fatal skips (detached HEAD, no upstream, etc.) as warnings
        // so operators are aware without crashing the scheduler.
        if ($result['skipped_reason'] !== '') {
            Log::warning('git:check-updates — skipped: '.$result['skipped_reason'].
                ($result['error'] !== '' ? ' ('.$result['error'].')' : ''));
        }

        // Persist — use Cache::forever so the value survives until the next
        // check overwrites it (no TTL expiry mid-day that would hide state).
        Cache::forever(self::CACHE_KEY, $result);

        if ($result['skipped_reason'] !== '') {
            $this->line('<comment>Check skipped ('.$result['skipped_reason'].').</comment>');

            return self::SUCCESS;
        }

        if ($result['available']) {
            $this->line('<info>Update available — '.$result['commits_behind'].' commit(s) behind remote.</info>');
        } else {
            $this->line('<info>Up to date.</info>');
        }

        return self::SUCCESS;
    }
}
