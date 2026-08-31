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

        $record = $this->attachLocalPdfBytes($this->record);
        $timeout = isset($record['document_base64']) ? 120 : 45;

        $payload = [
            'type' => $this->type,
            'table' => $this->table,
            'record' => $record,
            'old_record' => $this->oldRecord,
            'correlation_id' => $this->correlationId,
        ];

        $response = Http::timeout($timeout)
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
            'pdf_attached' => isset($record['document_base64']),
        ]);
    }

    /**
     * AWS cannot fetch Laravel's localhost upload URL. Attach the PDF bytes
     * so the AI service can extract text without an HTTP round-trip.
     *
     * @param  array<string, mixed>  $record
     * @return array<string, mixed>
     */
    private function attachLocalPdfBytes(array $record): array
    {
        $format = strtolower(trim((string) ($record['document_format'] ?? '')));
        $url = trim((string) ($record['document_url'] ?? ''));
        if ($format !== 'pdf' || $url === '') {
            return $record;
        }

        $path = $this->resolveLocalUploadPath($url);
        if ($path === null || !is_file($path) || !is_readable($path)) {
            if ($path !== null) {
                Log::warning('AI policy sync PDF file missing on disk', [
                    'correlation_id' => $this->correlationId,
                    'path' => $path,
                ]);
            }
            return $record;
        }

        $size = filesize($path);
        $maxBytes = 10 * 1024 * 1024;
        if ($size === false || $size > $maxBytes) {
            Log::warning('AI policy sync skipped PDF attach: unreadable or too large', [
                'correlation_id' => $this->correlationId,
                'bytes' => $size,
            ]);
            return $record;
        }

        $bytes = file_get_contents($path);
        if ($bytes === false || $bytes === '') {
            return $record;
        }

        $record['document_base64'] = base64_encode($bytes);
        return $record;
    }

    private function resolveLocalUploadPath(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            return null;
        }

        $filename = basename($path);
        if ($filename === '' || str_contains($filename, '..')) {
            return null;
        }
        if (strtolower((string) pathinfo($filename, PATHINFO_EXTENSION)) !== 'pdf') {
            return null;
        }

        return public_path('uploads/' . $filename);
    }
}
