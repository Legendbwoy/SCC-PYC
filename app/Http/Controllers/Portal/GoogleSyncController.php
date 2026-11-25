<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Integrations\Models\IntegrationConnection;
use App\Modules\Integrations\Models\SyncLog;
use App\Modules\Membership\Models\MemberSubmission;
use App\Modules\Membership\Services\MemberSyncService;
use App\Support\Audit\AuditLogger;
use App\Support\Security\PycPermission;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GoogleSyncController extends Controller
{
    public function __construct(
        private readonly MemberSyncService $syncService,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()?->can(PycPermission::GoogleSyncView->value), 403);

        $connection = IntegrationConnection::query()->firstOrCreate(
            ['provider' => 'google_sheets'],
            [
                'name' => 'Google Forms Membership Spreadsheet',
                'spreadsheet_id' => (string) config('pyc.membership.google_sheets.spreadsheet_id', ''),
                'sheet_range' => (string) config('pyc.membership.google_sheets.sheet_range', 'Form Responses 1!A1:Z2000'),
                'status' => 'configured',
            ]
        );

        return view('portal.membership.google-sync', [
            'connection' => $connection,
            'syncLogs' => SyncLog::query()->with('initiator')->where('provider', 'google_sheets')->latest('id')->paginate(10),
            'failedSubmissions' => MemberSubmission::query()
                ->where('sync_status', 'validation_failed')
                ->latest('id')
                ->limit(15)
                ->get(),
            'credentialsConfigured' => trim((string) config('pyc.membership.google_sheets.service_account_json_path', '')) !== ''
                || trim((string) config('pyc.membership.google_sheets.api_key', '')) !== '',
        ]);
    }

    public function updateConnection(Request $request): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        abort_unless($actor->can(PycPermission::GoogleSyncRun->value), 403);

        $validated = $request->validate([
            'spreadsheet_id' => ['required', 'string', 'max:160'],
            'sheet_range' => ['required', 'string', 'max:120'],
        ]);

        $connection = IntegrationConnection::query()->updateOrCreate(
            ['provider' => 'google_sheets'],
            [
                'name' => 'Google Forms Membership Spreadsheet',
                'spreadsheet_id' => trim($validated['spreadsheet_id']),
                'sheet_range' => trim($validated['sheet_range']),
                'updated_by' => $actor->id,
            ]
        );

        $this->auditLogger->log(
            event: 'google_sync.connection_updated',
            category: 'membership',
            description: 'Updated Google Sheets spreadsheet ID and range.',
            auditable: $connection,
            actor: $actor,
        );

        return redirect()
            ->route('membership.google-sync')
            ->with('status', 'Google Sheets connection settings saved.');
    }

    public function runSync(Request $request): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        abort_unless($actor->can(PycPermission::GoogleSyncRun->value), 403);

        $log = $this->syncService->sync($actor, 'manual');

        if ($log->status === 'failed') {
            return redirect()
                ->route('membership.google-sync')
                ->withErrors(['sync' => "Synchronization failed: {$log->error_summary}"]);
        }

        return redirect()
            ->route('membership.google-sync')
            ->with('status', "Sync {$log->status}: {$log->imported_count} imported ({$log->duplicate_flagged_count} duplicate-flagged), {$log->skipped_count} skipped, {$log->failed_count} failed.");
    }

    public function retrySync(Request $request, SyncLog $syncLog): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        abort_unless($actor->can(PycPermission::GoogleSyncRun->value), 403);

        $retryLog = $this->syncService->retryFailed((int) $syncLog->id, $actor);

        return redirect()
            ->route('membership.google-sync')
            ->with('status', "Reprocessed failed rows: {$retryLog->imported_count} imported, {$retryLog->failed_count} still failing.");
    }
}
