<?php

declare(strict_types=1);

namespace App\Modules\Membership\Services;

use App\Models\User;
use App\Modules\Integrations\Models\IntegrationConnection;
use App\Modules\Integrations\Models\SyncLog;
use App\Modules\Membership\Contracts\GoogleSheetsClientInterface;
use App\Modules\Membership\Models\MemberSubmission;
use App\Support\Audit\AuditLogger;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Idempotent Google Forms / Sheets response synchronization service (Spec Section 5.1–5.4 & 12.1).
 */
class MemberSyncService
{
    public function __construct(
        private readonly GoogleSheetsClientInterface $sheetsClient,
        private readonly MemberValidationService $validationService,
        private readonly MemberRegistrationService $registrationService,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function sync(?User $actor = null, string $triggerType = 'manual'): SyncLog
    {
        $connection = IntegrationConnection::query()->firstOrCreate(
            ['provider' => 'google_sheets'],
            [
                'name' => 'Google Forms Membership Spreadsheet',
                'spreadsheet_id' => (string) config('pyc.membership.google_sheets.spreadsheet_id', ''),
                'sheet_range' => (string) config('pyc.membership.google_sheets.sheet_range', 'Form Responses 1!A1:Z2000'),
                'status' => 'configured',
            ]
        );

        $syncLog = SyncLog::query()->create([
            'provider' => 'google_sheets',
            'trigger_type' => $triggerType,
            'status' => 'running',
            'started_at' => now(),
            'initiated_by' => $actor?->id,
        ]);

        try {
            $rows = $this->sheetsClient->fetchResponses();
        } catch (\Throwable $exception) {
            $syncLog->update([
                'status' => 'failed',
                'error_summary' => $exception->getMessage(),
                'finished_at' => now(),
            ]);

            $this->auditLogger->log(
                event: 'google_sync.failed',
                category: 'membership',
                description: 'Google Forms synchronization failed: '.$exception->getMessage(),
                auditable: $syncLog,
                actor: $actor,
            );

            return $syncLog->fresh() ?? $syncLog;
        }

        $customMapping = (array) ($connection->field_mapping ?? []);
        $imported = 0;
        $skipped = 0;
        $duplicates = 0;
        $failed = 0;
        $errors = [];
        $maxRowCursor = $connection->last_cursor;

        foreach ($rows as $row) {
            $externalId = (string) $row['external_response_id'];
            /** @var array<string, mixed> $rawPayload */
            $rawPayload = (array) $row['payload'];
            $maxRowCursor = (string) ($row['row_number'] ?? $maxRowCursor);

            // Idempotency check: never import the same (source, external_response_id) twice
            $existing = MemberSubmission::query()
                ->where('source', 'google_forms')
                ->where('external_response_id', $externalId)
                ->first();

            if ($existing !== null && $existing->member_id !== null) {
                $skipped++;

                continue;
            }

            $submission = $existing ?? MemberSubmission::query()->create([
                'sync_log_id' => $syncLog->id,
                'source' => 'google_forms',
                'external_response_id' => $externalId,
                'raw_payload' => $rawPayload,
                'sync_status' => 'imported',
            ]);

            try {
                $validated = $this->validationService->validate($rawPayload, $customMapping);
                $member = $this->registrationService->createPending($validated, $submission, $actor);

                $imported++;
                if ($member->is_duplicate_flagged) {
                    $duplicates++;
                }
            } catch (ValidationException $validationException) {
                $failed++;
                $submission->update([
                    'sync_log_id' => $syncLog->id,
                    'sync_status' => 'validation_failed',
                    'validation_errors' => $validationException->errors(),
                    'processed_at' => now(),
                ]);
                $errors[$externalId] = $validationException->errors();
            }
        }

        $finalStatus = $failed > 0 ? 'completed_with_errors' : 'completed';

        $syncLog->update([
            'status' => $finalStatus,
            'fetched_count' => count($rows),
            'imported_count' => $imported,
            'skipped_count' => $skipped,
            'duplicate_flagged_count' => $duplicates,
            'failed_count' => $failed,
            'error_summary' => $failed > 0 ? "{$failed} row(s) failed field validation." : null,
            'error_details' => $errors !== [] ? $errors : null,
            'finished_at' => now(),
        ]);

        $connection->update([
            'last_cursor' => $maxRowCursor,
            'last_synced_at' => now(),
            'status' => 'active',
            'updated_by' => $actor?->id,
        ]);

        $this->auditLogger->log(
            event: 'google_sync.completed',
            category: 'membership',
            description: "Google Forms sync completed: {$imported} imported ({$duplicates} duplicate-flagged), {$skipped} skipped, {$failed} failed.",
            auditable: $syncLog,
            actor: $actor,
        );

        return $syncLog->fresh() ?? $syncLog;
    }

    public function syncResponse(string $responseId, ?User $actor = null): MemberSubmission
    {
        $submission = MemberSubmission::query()
            ->where('source', 'google_forms')
            ->where('external_response_id', $responseId)
            ->first();

        if ($submission === null) {
            $rows = $this->sheetsClient->fetchResponses();
            $matched = collect($rows)->firstWhere('external_response_id', $responseId);

            if ($matched === null) {
                throw new RuntimeException("External response [{$responseId}] not found in Google Sheet.");
            }

            $submission = MemberSubmission::query()->create([
                'source' => 'google_forms',
                'external_response_id' => $responseId,
                'raw_payload' => (array) $matched['payload'],
                'sync_status' => 'imported',
            ]);
        }

        if ($submission->member_id !== null) {
            return $submission;
        }

        $connection = IntegrationConnection::query()->where('provider', 'google_sheets')->first();
        $validated = $this->validationService->validate(
            (array) $submission->raw_payload,
            (array) ($connection?->field_mapping ?? [])
        );

        $this->registrationService->createPending($validated, $submission, $actor);

        return $submission->fresh(['member']) ?? $submission;
    }

    public function retryFailed(int $syncLogId, ?User $actor = null): SyncLog
    {
        $failedSubmissions = MemberSubmission::query()
            ->where('sync_log_id', $syncLogId)
            ->where('sync_status', 'validation_failed')
            ->whereNull('member_id')
            ->get();

        $connection = IntegrationConnection::query()->where('provider', 'google_sheets')->first();
        $customMapping = (array) ($connection?->field_mapping ?? []);

        $retryLog = SyncLog::query()->create([
            'provider' => 'google_sheets',
            'trigger_type' => 'retry',
            'status' => 'running',
            'fetched_count' => $failedSubmissions->count(),
            'started_at' => now(),
            'initiated_by' => $actor?->id,
        ]);

        $imported = 0;
        $duplicates = 0;
        $stillFailed = 0;
        $errors = [];

        foreach ($failedSubmissions as $submission) {
            try {
                $validated = $this->validationService->validate((array) $submission->raw_payload, $customMapping);
                $member = $this->registrationService->createPending($validated, $submission, $actor);
                $imported++;
                if ($member->is_duplicate_flagged) {
                    $duplicates++;
                }
            } catch (ValidationException $e) {
                $stillFailed++;
                $submission->update([
                    'validation_errors' => $e->errors(),
                    'processed_at' => now(),
                ]);
                $errors[$submission->external_response_id] = $e->errors();
            }
        }

        $retryLog->update([
            'status' => $stillFailed > 0 ? 'completed_with_errors' : 'completed',
            'imported_count' => $imported,
            'duplicate_flagged_count' => $duplicates,
            'failed_count' => $stillFailed,
            'error_details' => $errors !== [] ? $errors : null,
            'finished_at' => now(),
        ]);

        return $retryLog;
    }
}
