<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SyncPolicyToAiJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public function __construct(
        public string $type,
        public string $table,
        public array $record,
        public ?array $oldRecord = null,
        public ?string $correlationId = null,
    ) {
        $this->correlationId = $correlationId ?: (string) Str::uuid();
    }

    public function handle(): void
    {
        $webhookUrl = config('services.ai.policy_sync_webhook_url');
        if (!$webhookUrl) {
            Log::warning('AI policy sync skipped: AI_POLICY_SYNC_WEBHOOK_URL unset', [
                'correlation_id' => $this->correlationId,
                'table' => $this->table,
                'type' => $this->type,
            ]);
            return;
        }

        $payload = [
            'type' => $this->type,
            'table' => $this->table,
            'record' => $this->record,
            'old_record' => $this->oldRecord,
            'correlation_id' => $this->correlationId,
        ];

        $response = Http::timeout(45)
            ->withHeaders([
                'X-Request-Id' => $this->correlationId,
                'Idempotency-Key' => $this->correlationId,
            ])
            ->post($webhookUrl, $payload);

        if (!$response->successful()) {
            Log::error('AI policy sync webhook failed', [
                'correlation_id' => $this->correlationId,
                'status' => $response->status(),
                'body' => $response->body(),
                'table' => $this->table,
                'type' => $this->type,
            ]);
            $response->throw();
        }

        Log::info('AI policy sync webhook accepted', [
            'correlation_id' => $this->correlationId,
            'table' => $this->table,
            'type' => $this->type,
            'policy_id' => $this->record['id'] ?? null,
        ]);
    }
}
