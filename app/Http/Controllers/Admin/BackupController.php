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
     * Restore data from an uploaded JSON backup file.
     *
     * POST /admin/backup/restore
     */
    public function restore(Request $request): RedirectResponse
    {
        $request->validate([
            'backup_file' => ['required', 'file', 'mimes:json', 'max:51200'],
        ]);

        try {
            $contents = file_get_contents($request->file('backup_file')->getRealPath());
            $data     = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

            $this->backupService->restore($data);

            return redirect()->route('admin.backup.index')
                ->with('success', __('Backup restored successfully.'));
        } catch (\Throwable $e) {
            return redirect()->route('admin.backup.index')
                ->with('error', __('Restore failed: :message', ['message' => $e->getMessage()]));
        }
    }
}
