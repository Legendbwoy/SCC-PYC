<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\Membership\Services\MemberSyncService;
use Illuminate\Console\Command;

class SyncGoogleMembersCommand extends Command
{
    protected $signature = 'pyc:sync-google-members';

    protected $description = 'Synchronize new Google Forms membership submissions into pending PYC applications.';

    public function handle(MemberSyncService $syncService): int
    {
        $log = $syncService->sync(actor: null, triggerType: 'scheduled');

        if ($log->status === 'failed') {
            $this->error("Google Forms sync failed: {$log->error_summary}");

            return self::FAILURE;
        }

        $this->info(
            "Sync [{$log->status}]: {$log->imported_count} imported ({$log->duplicate_flagged_count} duplicates), {$log->skipped_count} skipped, {$log->failed_count} failed."
        );

        return self::SUCCESS;
    }
}
