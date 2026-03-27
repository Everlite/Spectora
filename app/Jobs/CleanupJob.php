<?php

namespace App\Jobs;

use App\Models\ChecksHistory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CleanupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        //
    }

    public function handle(): void
    {
        $retentionDays = 90;
        $targetDate = now()->subDays($retentionDays);

        $deleted = ChecksHistory::where('checked_at', '<', $targetDate)->delete();

        Log::info("CleanupJob: Deleted $deleted old check history records older than $retentionDays days.");
    }
}
