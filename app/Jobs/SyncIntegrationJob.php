<?php

namespace App\Jobs;

use App\Models\Integration;
use App\Solar\Services\SyncService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncIntegrationJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable, SerializesModels;

    /** Retry up to 3 times before moving to failed_jobs. */
    public int $tries = 3;

    public function __construct(
        public readonly Integration $integration,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(SyncService $syncService): void
    {
        $this->integration->loadMissing(['provider', 'place.group']);
        $syncService->sync($this->integration);
    }

    /**
     * Quick retries: 10s, 20s, 30s — all attempts complete within one polling cycle.
     * The next scheduled run at t+60s acts as a natural recovery mechanism.
     */
    public function backoff(): array
    {
        return [10, 20, 30];
    }

    /**
     * Prevent multiple simultaneous syncs for the same integration.
     * ShouldBeUnique uses this as the cache key.
     */
    public function uniqueId(): string
    {
        return 'sync-integration-'.$this->integration->id;
    }

    /**
     * Handle a permanently failed job after all retries are exhausted.
     */
    public function failed(?Throwable $exception): void
    {
        Log::critical("Integration sync permanently failed for #{$this->integration->id}", [
            'integration_id' => $this->integration->id,
            'label' => $this->integration->label,
            'error' => $exception?->getMessage(),
        ]);
    }
}
