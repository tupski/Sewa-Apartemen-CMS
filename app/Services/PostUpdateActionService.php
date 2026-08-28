<?php

namespace App\Services;

use Symfony\Component\Process\Process;

/**
 * PostUpdateActionService
 *
 * Two jobs for the admin Version Control (Git) dashboard:
 *
 *  1. detect() — map a list of changed file paths (from `git diff --name-only`)
 *     to the set of post-update action keys that are actually required. When
 *     nothing relevant changed it returns an empty array, so the UI can hide
 *     every action button.
 *
 *  2. run() — execute the command(s) bound to an action key.
 *
 * SECURITY (SEC-03): commands are a FIXED server-side allowlist keyed by a short
 * action name. The client only ever sends the key; the argv arrays live here and
 * are hardcoded. Every command is spawned through Symfony Process with an
 * argument ARRAY — never a shell string — so no request data can reach a shell.
 */
class PostUpdateActionService
{
    /** Canonical display/run order of action keys. */
    private const ORDER = ['composer', 'assets_ci', 'assets', 'migrate', 'caches'];

    /** Files whose change requires an asset rebuild. */
    private const ASSET_FILES = [
        'package.json',
        'package-lock.json',
        'tailwind.config.js',
        'vite.config.js',
        'postcss.config.js',
    ];

    /**
     * Hardcoded allowlist: action key => list of argv arrays, run in order.
     *
     * @return array<string, array<int, array<int, string>>>
     */
    public static function commands(): array
    {
        $php = PHP_BINARY ?: 'php';

        return [
            'composer'  => [['composer', 'install', '--no-dev', '--optimize-autoloader']],
            // npm ci when the lockfile itself changed, npm install otherwise.
            'assets_ci' => [['npm', 'ci'], ['npm', 'run', 'build']],
            'assets'    => [['npm', 'install'], ['npm', 'run', 'build']],
            'migrate'   => [[$php, 'artisan', 'migrate', '--force']],
            'caches'    => [
                [$php, 'artisan', 'optimize:clear'],
                [$php, 'artisan', 'config:cache'],
                [$php, 'artisan', 'route:cache'],
            ],
        ];
    }

    /** @return array<int, string> */
    public static function allowedKeys(): array
    {
        return array_keys(self::commands());
    }

    /**
     * Derive the required post-update action keys from a changed-file list.
     *
     * @param  array<int, string>  $files  paths relative to the repo root
     * @return array<int, string>          action keys, canonical order
     */
    public function detect(array $files): array
    {
        $needed = [];
        $assets = false;
        $lockChanged = false;

        foreach ($files as $file) {
            $path = ltrim(str_replace('\\', '/', trim((string) $file)), '/');
            if ($path === '') {
                continue;
            }

            if ($path === 'composer.json' || $path === 'composer.lock') {
                $needed['composer'] = true;
            }

            if ($path === 'package-lock.json') {
                $lockChanged = true;
            }

            if (in_array($path, self::ASSET_FILES, true)
                || str_starts_with($path, 'resources/js/')
                || str_starts_with($path, 'resources/css/')) {
                $assets = true;
            }

            if (str_starts_with($path, 'database/migrations/')) {
                $needed['migrate'] = true;
            }

            if (str_starts_with($path, 'config/')
                || str_starts_with($path, 'routes/')
                || $path === '.env.example') {
                $needed['caches'] = true;
            }
        }

        if ($assets) {
            $needed[$lockChanged ? 'assets_ci' : 'assets'] = true;
        }

        return array_values(array_filter(self::ORDER, fn ($key) => isset($needed[$key])));
    }

    /**
     * Run every command bound to $key and return combined stdout+stderr.
     *
     * @throws \InvalidArgumentException on an unknown key
     * @throws \RuntimeException         when a command exits non-zero
     */
    public function run(string $key): string
    {
        $commands = self::commands()[$key] ?? null;

        if ($commands === null) {
            throw new \InvalidArgumentException('Unknown post-update action.');
        }

        $output = '';

        foreach ($commands as $argv) {
            // Argument array — no shell involved, nothing interpolated.
            $process = new Process($argv, base_path(), ['CI' => '1']);
            $process->setTimeout(900);
            $process->run();

            $output .= '$ ' . implode(' ', $argv) . "\n"
                . trim($process->getOutput() . "\n" . $process->getErrorOutput()) . "\n\n";

            if (! $process->isSuccessful()) {
                throw new \RuntimeException(sprintf(
                    "%s\nCommand failed with exit code %s.",
                    $output,
                    $process->getExitCode()
                ));
            }
        }

        return trim($output);
    }
}
