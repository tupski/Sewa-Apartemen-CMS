<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class BackupController extends Controller
{
    public function __construct(
        private readonly BackupService $backupService
    ) {}

    /**
     * Display the backup & restore page.
     */
    public function index(): View
    {
        return view('admin.backup.index');
    }

    /**
     * Generate and stream a JSON backup file for download.
     *
     * POST /admin/backup/download
     */
    public function download(Request $request): Response
    {
        $request->validate([
            'groups'   => ['required', 'array', 'min:1'],
            'groups.*' => ['string', 'in:full,settings,blog,properties,bookings,vouchers,pages,users,media,seo'],
        ]);

        $groups   = $request->input('groups');
        $data     = $this->backupService->export($groups);

        // Build the filename: backup-<groups>-<date>.json
        $groupLabel = in_array('full', $groups, true)
            ? 'full'
            : implode('-', $groups);

        $filename = 'backup-' . $groupLabel . '-' . now()->format('Y-m-d') . '.json';

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return response($json, 200, [
            'Content-Type'        => 'application/json',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Step 1 — Upload a backup file and check whether existing data would be
     * overwritten.  If the destination tables are empty, restore immediately.
     * If they contain data, store the uploaded file temporarily and render
     * the confirmation dialog.
     *
     * POST /admin/backup/restore
     */
    public function restore(Request $request): RedirectResponse|View
    {
        $request->validate([
            'backup_file' => ['required', 'file', 'mimes:json', 'max:51200'],
        ]);

        try {
            $contents = file_get_contents($request->file('backup_file')->getRealPath());
            $data     = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

            // No existing data → restore immediately without asking.
            if (! $this->backupService->hasExistingData($data)) {
                $this->backupService->restore($data);

                return redirect()->route('admin.backup.index')
                    ->with('success', __('Backup restored successfully.'));
            }

            // Existing data found → store file temporarily so the confirmation
            // step can pick it up, then show the confirmation view.
            $this->cleanupStalePendingRestores();

            $stored = $request->file('backup_file')->store('pending-restores', 'local');

            return view('admin.backup.index', [
                'pendingRestore' => $stored,
            ]);
        } catch (\Throwable $e) {
            return redirect()->route('admin.backup.index')
                ->with('error', __('Restore failed: :message', ['message' => $e->getMessage()]));
        }
    }

    /**
     * Step 2 — Execute the restore after the user has confirmed overwrite.
     *
     * POST /admin/backup/restore/confirm
     */
    public function confirmRestore(Request $request): RedirectResponse
    {
        $request->validate([
            'pending_file' => ['required', 'string'],
        ]);

        // Resolve the real path and make sure it stays inside the
        // pending-restores directory (blocks path-traversal attempts).
        $baseDir  = realpath(storage_path('app/private/pending-restores'));
        $candidate = realpath(storage_path('app/private/' . $request->input('pending_file')));

        if ($baseDir === false
            || $candidate === false
            || ! str_starts_with($candidate, $baseDir . DIRECTORY_SEPARATOR)
        ) {
            return redirect()->route('admin.backup.index')
                ->with('error', __('Restore failed: pending file not found. Please upload again.'));
        }

        try {
            $contents = file_get_contents($candidate);
            $data     = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

            $this->backupService->restore($data);

            @unlink($candidate);

            return redirect()->route('admin.backup.index')
                ->with('success', __('Backup restored successfully.'));
        } catch (\Throwable $e) {
            return redirect()->route('admin.backup.index')
                ->with('error', __('Restore failed: :message', ['message' => $e->getMessage()]));
        }
    }

    /**
     * Remove abandoned pending-restore files that are older than 1 hour
     * so the private storage directory does not accumulate stale uploads.
     */
    private function cleanupStalePendingRestores(): void
    {
        $dir = storage_path('app/private/pending-restores');

        foreach (glob($dir . '/*.json') ?: [] as $file) {
            if (is_file($file) && filemtime($file) < now()->subHour()->getTimestamp()) {
                @unlink($file);
            }
        }
    }
}
