<?php

namespace App\Services;

use Symfony\Component\Process\Process;

/**
 * GitService
 *
 * Provides git operations for the admin Version Control dashboard:
 * remote origin info, commit history, rollback (git checkout <commit>),
 * and detached-HEAD recovery.
 *
 * SECURITY: every git/shell invocation goes through Symfony Process with an
 * argument ARRAY — never a shell string. No user-controlled data reaches a
 * shell interpreter.
 */
class GitService
{
    /**
     * Default number of commits to display.
     */
    public const COMMIT_DISPLAY_LIMIT = 5;

    /**
     * Increment for "Show more" — a single named constant, not a magic number.
     */
    public const SHOW_MORE_INCREMENT = 20;

    /**
     * Regex for validating a commit SHA (7-40 hex characters).
     */
    public const SHA_PATTERN = '/^[0-9a-f]{7,40}$/';

    /**
     * Branch candidates tried in order when detecting the default branch.
     */
    private const DEFAULT_BRANCH_CANDIDATES = ['main', 'master'];

    /**
     * Field delimiter for git log output: \x1f (unit separator).
     * Cannot appear in any commit metadata field.
     */
    private const FIELD_DELIM = "\x1f";

    /**
     * Record delimiter for git log output: \x1e (record separator).
     * Cannot appear in any commit message.
     */
    private const RECORD_DELIM = "\x1e";

    /**
     * Timezone used for all commit-time display (Asia/Jakarta / WIB).
     */
    public const DISPLAY_TIMEZONE = 'Asia/Jakarta';

    /**
     * Run a git command via Symfony Process with an argument array.
     * Returns stdout (trimmed) or throws on failure.
     *
     * @param  array<int, string>  $args
     * @param  array<string, string>  $env
     */
    public function runGit(array $args, string $cwd, bool $throwOnFailure = false, array $env = []): string
    {
        $process = new Process(
            array_merge(['git', '-C', $cwd], $args),
            null,
            $env,
            null,
            900
        );

        $process->run();

        if (! $process->isSuccessful()) {
            $message = sprintf(
                "Git command failed (exit %d): git %s\nOutput: %s\nError: %s",
                $process->getExitCode(),
                implode(' ', $args),
                trim($process->getOutput()),
                trim($process->getErrorOutput())
            );

            if ($throwOnFailure) {
                throw new \RuntimeException($message);
            }

            return '';
        }

        return trim($process->getOutput());
    }

    /**
     * Validate a commit SHA string: must match /^[0-9a-f]{7,40}$/ and resolve
     * to a real commit object in this repository.
     *
     * @return array{valid: bool, full_hash: string, error: string}
     */
    public function validateCommitSha(string $sha, string $cwd): array
    {
        if (! preg_match(self::SHA_PATTERN, $sha)) {
            return [
                'valid' => false,
                'full_hash' => '',
                'error' => __('git.sha_invalid_format'),
            ];
        }

        // Verify the SHA resolves to a real commit object.
        $type = $this->runGit(['cat-file', '-t', $sha], $cwd);

        if ($type !== 'commit') {
            return [
                'valid' => false,
                'full_hash' => '',
                'error' => __('git.sha_not_found'),
            ];
        }

        // Resolve to the full hash (canonical 40-char form).
        $fullHash = trim($this->runGit(['rev-parse', $sha], $cwd));

        return [
            'valid' => true,
            'full_hash' => $fullHash,
            'error' => '',
        ];
    }

    /**
     * Redact embedded credentials from a remote URL.
     *
     * E.g. "https://user:token@github.com/..." → "https://***@github.com/..."
     */
    public function redactRemoteUrl(?string $url): string
    {
        if ($url === '' || $url === null) {
            return '';
        }

        return preg_replace('#://[^/@]+@#', '://***@', $url) ?? $url;
    }

    /**
     * Get remote origin information: URL (credential-redacted), current branch,
     * upstream tracking branch, and detached-HEAD status.
     *
     * @return array{remote_url: string, branch: string, is_detached: bool, upstream: string, detached_short: string}
     */
    public function getRemoteInfo(string $cwd): array
    {
        $remoteUrl = trim($this->runGit(['remote', 'get-url', 'origin'], $cwd));
        $branch = trim($this->runGit(['rev-parse', '--abbrev-ref', 'HEAD'], $cwd));
        $upstream = trim($this->runGit(['rev-parse', '--abbrev-ref', '--symbolic-full-name', '@{upstream}'], $cwd));

        $isDetached = $branch === 'HEAD';
        $detachedShort = '';

        if ($isDetached) {
            $detachedShort = trim($this->runGit(['rev-parse', '--short', 'HEAD'], $cwd));
        }

        return [
            'remote_url' => $this->redactRemoteUrl($remoteUrl),
            'branch' => $isDetached ? '' : $branch,
            'is_detached' => $isDetached,
            'upstream' => $upstream,
            'detached_short' => $detachedShort,
        ];
    }

    /**
     * Parse ref names from git log %D format into a clean list of branch names.
     * Filters out tag: prefixes, HEAD, symbolic "->" suffixes, and remote refs.
     *
     * @return array<int, string>
     */
    public function parseRefNames(?string $refs): array
    {
        if ($refs === '' || $refs === null) {
            return [];
        }

        $branches = [];

        foreach (explode(', ', $refs) as $part) {
            $part = trim($part);

            // Skip tag: prefix refs.
            if (str_starts_with($part, 'tag: ')) {
                continue;
            }

            // Skip the symbolic "HEAD -> branch" pointer.
            if (str_starts_with($part, 'HEAD -> ')) {
                continue;
            }

            // Skip bare HEAD marker.
            if ($part === 'HEAD') {
                continue;
            }

            // Strip any remaining "-> " symbolic suffix.
            $part = preg_replace('/\s*->.*$/', '', $part) ?? $part;

            // Normalize remote refs to short branch names.
            if (str_starts_with($part, 'refs/remotes/')) {
                $part = preg_replace('#^refs/remotes/[^/]+/#', '', $part) ?? $part;
            }

            if ($part !== '') {
                $branches[] = $part;
            }
        }

        return array_values(array_unique($branches));
    }

    /**
     * Get the commit history.
     *
     * Parses `git log --all` output produced with an explicit pretty format
     * using %x1f (unit separator) between fields and %x1e (record separator)
     * between commits. Neither byte can appear in a commit subject or author
     * name, so rows cannot be corrupted by exotic commit messages.
     *
     * Fields: %H full hash, %h short hash, %s subject, %an author name,
     * %cI committer date (ISO-8601 with tz), %D ref names.
     *
     * @param  int  $limit  Number of commits to return (starting from latest).
     * @param  int  $skip  Number of commits to skip (for pagination).
     * @return array<int, array{full_hash: string, short_hash: string, subject: string, author: string, date_iso: string, branches: array<int, string>, is_head: bool}>
     */
    public function getCommitHistory(string $cwd, int $limit = self::COMMIT_DISPLAY_LIMIT, int $skip = 0): array
    {
        $format = '%H'.self::FIELD_DELIM
            .'%h'.self::FIELD_DELIM
            .'%s'.self::FIELD_DELIM
            .'%an'.self::FIELD_DELIM
            .'%cI'.self::FIELD_DELIM
            .'%D'.self::RECORD_DELIM;

        $raw = $this->runGit([
            'log',
            '--all',
            '--max-count='.$limit,
            '--skip='.$skip,
            '--pretty=format:'.$format,
        ], $cwd, true);

        if ($raw === '') {
            return [];
        }

        $headHash = trim($this->runGit(['rev-parse', 'HEAD'], $cwd));
        $commits = [];

        foreach (explode(self::RECORD_DELIM, $raw) as $record) {
            $record = trim($record);
            if ($record === '') {
                continue;
            }

            $parts = explode(self::FIELD_DELIM, $record);
            if (count($parts) < 6) {
                continue;
            }

            $fullHash = trim($parts[0]);

            $commits[] = [
                'full_hash' => $fullHash,
                'short_hash' => trim($parts[1]),
                'subject' => trim($parts[2]),
                'author' => trim($parts[3]),
                'date_iso' => trim($parts[4]),
                'branches' => $this->parseRefNames($parts[5] ?? ''),
                'is_head' => $fullHash === $headHash,
            ];
        }

        return $commits;
    }

    /**
     * Format a commit timestamp for display.
     *
     * Exact rule: if the commit is less than 1 day old, return a relative human
     * string (e.g. "5 menit yang lalu"). If 1 day or older, return the full date
     * and time as "DD/MM/YYYY HH:mm" in Asia/Jakarta (WIB).
     *
     * The relative vs absolute decision and the "DD/MM/YYYY HH:mm" layout are
     * both enforced here (unit-testable) and never delegated to git's own
     * relative-date strings.
     */
    public function formatCommitTime(string $iso8601Date, ?\DateTimeImmutable $now = null): string
    {
        $timezone = new \DateTimeZone(self::DISPLAY_TIMEZONE);

        try {
            $commitTime = (new \DateTimeImmutable($iso8601Date))->setTimezone($timezone);
        } catch (\Exception) {
            return $iso8601Date;
        }

        $now = ($now ?? new \DateTimeImmutable('now', $timezone))->setTimezone($timezone);

        // PHP's DateTimeImmutable::diff() returns invert=1 when the second date
        // ($commitTime) is BEFORE the first ($now), i.e. a past commit. invert=0
        // means $commitTime is in the future (clock skew) — always show exact.
        $diff = $now->diff($commitTime);

        if ($diff->invert === 0) {
            return $commitTime->format('d/m/Y H:i');
        }

        // $diff->days is `false` when the DateInterval was not computed from
        // the full calendar (e.g. same-day diffs across timezones). Treat
        // `false` as 0 (same day).
        $totalDays = is_int($diff->days) ? $diff->days : 0;

        // Less than 1 day old → relative string.
        if ($totalDays === 0 && $diff->y === 0 && $diff->m === 0 && $diff->d === 0) {
            if ($diff->h >= 1) {
                return __('git.time_hours_ago', ['count' => $diff->h]);
            }

            if ($diff->i >= 1) {
                return __('git.time_minutes_ago', ['count' => $diff->i]);
            }

            return __('git.time_just_now');
        }

        // 1 day or older → exact date.
        return $commitTime->format('d/m/Y H:i');
    }

    /**
     * Check whether the working tree has uncommitted changes.
     *
     * @return array{dirty: bool, status: string}
     */
    public function checkDirtyTree(string $cwd): array
    {
        $status = $this->runGit(['status', '--porcelain'], $cwd);

        return [
            'dirty' => $status !== '',
            'status' => $status,
        ];
    }

    /**
     * Perform a rollback: checkout a specific commit (detached HEAD).
     *
     * Steps (progress order):
     * 1. Dirty-tree guard — refuse if there are uncommitted local changes
     *    (we never use -f/--force to bulldoze them).
     * 2. Fetch from origin (best effort; a network failure does not abort).
     * 3. Checkout the target commit.
     * 4. Detect detached HEAD and report it.
     *
     * The commit SHA must already have been validated by validateCommitSha().
     *
     * @return array{success: bool, steps: array<int, array{label: string, status: string, detail: string}>}
     */
    public function rollback(string $sha, string $cwd): array
    {
        $steps = [];

        // Step 1: dirty-tree guard.
        $dirty = $this->checkDirtyTree($cwd);
        if ($dirty['dirty']) {
            $steps[] = [
                'label' => __('git.step_dirty_check'),
                'status' => 'failed',
                'detail' => __('git.rollback_dirty_tree'),
            ];

            return ['success' => false, 'steps' => $steps];
        }

        $steps[] = [
            'label' => __('git.step_dirty_check'),
            'status' => 'done',
            'detail' => __('git.step_dirty_check_ok'),
        ];

        // Step 2: fetch (best effort).
        try {
            $fetchOutput = trim($this->runGit(['fetch', 'origin'], $cwd, true));
            $steps[] = [
                'label' => __('git.step_fetch'),
                'status' => 'done',
                'detail' => $fetchOutput !== '' ? $fetchOutput : __('git.step_fetch_ok'),
            ];
        } catch (\RuntimeException $e) {
            $steps[] = [
                'label' => __('git.step_fetch'),
                'status' => 'warning',
                'detail' => __('git.step_fetch_skipped').' '.$e->getMessage(),
            ];
        }

        // Step 3: checkout the target commit.
        try {
            $checkoutOutput = trim($this->runGit(['checkout', $sha], $cwd, true));
            $steps[] = [
                'label' => __('git.step_checkout'),
                'status' => 'done',
                'detail' => $checkoutOutput !== '' ? $checkoutOutput : __('git.step_checkout_ok'),
            ];
        } catch (\RuntimeException $e) {
            $steps[] = [
                'label' => __('git.step_checkout'),
                'status' => 'failed',
                'detail' => $e->getMessage(),
            ];

            return ['success' => false, 'steps' => $steps];
        }

        // Step 4: verify detached HEAD.
        $branch = trim($this->runGit(['rev-parse', '--abbrev-ref', 'HEAD'], $cwd));
        $isDetached = $branch === 'HEAD';

        $steps[] = [
            'label' => __('git.step_detached_check'),
            'status' => 'done',
            'detail' => $isDetached
                ? __('git.rollback_detached_notice')
                : __('git.step_detached_check_ok'),
        ];

        // Step 5: schema note — rolling back code never rolls back migrations.
        $steps[] = [
            'label' => __('git.step_migration_note'),
            'status' => 'info',
            'detail' => __('git.rollback_schema_note'),
        ];

        return ['success' => true, 'steps' => $steps];
    }

    /**
     * Check whether the local working tree is behind its remote tracking branch.
     *
     * Performs a `git fetch` to refresh remote refs, then computes ahead/behind
     * counts between HEAD and the upstream.  The result is suitable for persisting
     * to cache so the admin header can read it cheaply on every page render without
     * ever touching git or the network (AGENTS.md §14).
     *
     * SECURITY: all git invocations use runGit() with argument arrays (AGENTS.md §15).
     * Remote URLs are redacted before they appear in any returned value (SEC-13).
     *
     * @return array{
     *   available:     bool,
     *   commits_behind: int,
     *   commits_ahead:  int,
     *   remote_sha:     string,
     *   remote_subject: string,
     *   remote_author:  string,
     *   remote_date:    string,
     *   checked_at:     string,
     *   error:          string,
     *   skipped_reason: string,
     * }
     */
    public function checkForUpdates(string $cwd): array
    {
        $empty = [
            'available' => false,
            'commits_behind' => 0,
            'commits_ahead' => 0,
            'remote_sha' => '',
            'remote_subject' => '',
            'remote_author' => '',
            'remote_date' => '',
            'checked_at' => now()->toIso8601String(),
            'error' => '',
            'skipped_reason' => '',
        ];

        // Guard: must actually be a git repository.
        if (! is_dir(rtrim($cwd, '/\\').DIRECTORY_SEPARATOR.'.git')) {
            return array_merge($empty, [
                'error' => 'Not a git repository.',
                'skipped_reason' => 'not_a_repo',
            ]);
        }

        // Guard: detached HEAD — rollback leaves the repo here and "behind" is not
        // meaningful without a tracking branch, so we report honestly instead of
        // returning a spurious update notice.
        $abbrevRef = trim($this->runGit(['rev-parse', '--abbrev-ref', 'HEAD'], $cwd));
        if ($abbrevRef === 'HEAD') {
            return array_merge($empty, [
                'skipped_reason' => 'detached_head',
            ]);
        }

        $branch = $abbrevRef;

        // Step 1: fetch from origin (network call — runs only in the scheduler/command).
        // GIT_TERMINAL_PROMPT=0 makes it fail-fast when credentials are absent.
        try {
            $this->runGit(['fetch', 'origin', '--quiet'], $cwd, true, ['GIT_TERMINAL_PROMPT' => '0']);
        } catch (\RuntimeException $e) {
            // Redact any embedded credentials before touching error messages.
            $safe = $this->redactRemoteUrl($e->getMessage());

            return array_merge($empty, [
                'error' => 'git fetch failed: '.$safe,
                'skipped_reason' => 'fetch_failed',
            ]);
        }

        // Step 2: resolve the upstream tracking ref.
        $upstream = trim($this->runGit(
            ['rev-parse', '--abbrev-ref', '--symbolic-full-name', '@{u}'],
            $cwd
        ));

        // If there is no upstream configured, fall back to origin/<branch>.
        if ($upstream === '') {
            $upstream = 'origin/'.$branch;
        }

        // Verify the upstream ref actually exists (e.g. brand new branch not yet pushed).
        $upstreamHash = trim($this->runGit(['rev-parse', '--verify', $upstream], $cwd));
        if ($upstreamHash === '') {
            return array_merge($empty, [
                'skipped_reason' => 'no_upstream',
            ]);
        }

        // Step 3: compute ahead/behind counts.
        $revList = trim($this->runGit(
            ['rev-list', '--left-right', '--count', 'HEAD...'.$upstream],
            $cwd
        ));

        [$ahead, $behind] = array_map('intval', preg_split('/\s+/', $revList) ?: ['0', '0']);

        $available = $behind > 0;

        // Step 4: if updates exist, fetch the latest remote commit metadata.
        $remoteSha = '';
        $remoteSubject = '';
        $remoteAuthor = '';
        $remoteDate = '';

        if ($available) {
            // Use the same field/record delimiters as getCommitHistory() for safety.
            $logRaw = trim($this->runGit(
                [
                    'log',
                    '-1',
                    '--pretty=format:%H'.self::FIELD_DELIM.'%s'.self::FIELD_DELIM.'%an'.self::FIELD_DELIM.'%cI',
                    $upstream,
                ],
                $cwd
            ));

            if ($logRaw !== '') {
                $parts = explode(self::FIELD_DELIM, $logRaw);
                $remoteSha = $parts[0] ?? '';
                $remoteSubject = $parts[1] ?? '';
                $remoteAuthor = $parts[2] ?? '';
                $remoteDate = $parts[3] ?? '';
            }
        }

        return [
            'available' => $available,
            'commits_behind' => $behind,
            'commits_ahead' => $ahead,
            'remote_sha' => $remoteSha,
            'remote_subject' => $remoteSubject,
            'remote_author' => $remoteAuthor,
            'remote_date' => $remoteDate,
            'checked_at' => now()->toIso8601String(),
            'error' => '',
            'skipped_reason' => '',
        ];
    }

    /**
     * Return to the default branch tip (e.g. main, master) from detached HEAD.
     *
     * @return array{success: bool, output: string, error: string, branch: string}
     */
    public function returnToBranchTip(string $cwd): array
    {
        $branch = '';

        foreach (self::DEFAULT_BRANCH_CANDIDATES as $candidate) {
            if ($this->runGit(['rev-parse', '--verify', $candidate], $cwd) !== '') {
                $branch = $candidate;
                break;
            }
        }

        if ($branch === '') {
            $originHead = $this->runGit(['symbolic-ref', 'refs/remotes/origin/HEAD'], $cwd);
            if ($originHead !== '') {
                $branch = preg_replace('#^refs/remotes/origin/#', '', trim($originHead)) ?? '';
            }
        }

        if ($branch === '') {
            return [
                'success' => false,
                'output' => '',
                'error' => __('git.return_no_branch'),
                'branch' => '',
            ];
        }

        try {
            $output = $this->runGit(['checkout', $branch], $cwd, true);

            return [
                'success' => true,
                'output' => $output,
                'error' => '',
                'branch' => $branch,
            ];
        } catch (\RuntimeException $e) {
            return [
                'success' => false,
                'output' => '',
                'error' => $e->getMessage(),
                'branch' => $branch,
            ];
        }
    }
}
