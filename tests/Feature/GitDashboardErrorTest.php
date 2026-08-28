<?php

namespace Tests\Feature;

use App\Http\Controllers\SettingsController;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Covers the SEC-13 categorized, safe-to-expose git error messages returned by
 * the admin Git dashboard. The categorization/sanitization logic is exercised
 * directly (deterministic, independent of the server's real git state) so the
 * "not a git repository" path and path/URL redaction are pinned by tests.
 */
class GitDashboardErrorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Category strings are resolved via __('...'); pin the locale so the
        // assertions match the English keys regardless of the test app locale.
        app()->setLocale('en');
    }

    private function gitErrorMessage(\Throwable $e): string
    {
        $method = new ReflectionMethod(SettingsController::class, 'gitErrorMessage');
        $method->setAccessible(true);

        return $method->invoke(app(SettingsController::class), $e);
    }

    public function test_not_a_git_repository_is_categorized(): void
    {
        $message = $this->gitErrorMessage(new \RuntimeException(
            'fatal: not a git repository: the .git directory was not found in the deploy path.'
        ));

        $this->assertStringContainsString('Not a git repository', $message);
    }

    public function test_dubious_ownership_is_categorized(): void
    {
        $message = $this->gitErrorMessage(new \RuntimeException(
            "fatal: detected dubious ownership in repository at '/var/www/app'"
        ));

        $this->assertStringContainsString('dubious repository ownership', $message);
    }

    public function test_authentication_failure_is_categorized(): void
    {
        $message = $this->gitErrorMessage(new \RuntimeException(
            'fatal: Authentication failed for https://example.com/repo.git'
        ));

        $this->assertStringContainsString('authentication failed', $message);
    }

    public function test_network_failure_is_categorized(): void
    {
        $message = $this->gitErrorMessage(new \RuntimeException(
            'fatal: unable to access https://example.com/repo.git: Could not resolve host: example.com'
        ));

        $this->assertStringContainsString('Could not reach the git remote', $message);
    }

    public function test_sensitive_paths_and_urls_are_redacted(): void
    {
        $message = $this->gitErrorMessage(new \RuntimeException(
            "Git command failed (exit 128): git fetch origin\n"
            . "Output: \n"
            . "Error: fatal: unable to access 'https://user:secret@example.com/repo.git': "
            . "Could not resolve host at /var/www/secret/path"
        ));

        // The category message must be present...
        $this->assertStringContainsString('Could not reach the git remote', $message);
        // ...but no remote URL, credentials, or absolute path may leak.
        $this->assertStringNotContainsString('secret', $message);
        $this->assertStringNotContainsString('example.com', $message);
        $this->assertStringNotContainsString('/var/www', $message);
    }
}
