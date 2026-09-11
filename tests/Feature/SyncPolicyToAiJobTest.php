<?php

namespace Tests\Feature;

use App\Jobs\SyncPolicyToAiJob;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SyncPolicyToAiJobTest extends TestCase
{
    public function test_attaches_local_pdf_bytes_to_webhook_payload(): void
    {
        config(['services.ai.policy_sync_webhook_url' => 'https://ai.example.test/webhooks/policy-sync']);
        Http::fake([
            'https://ai.example.test/*' => Http::response(['status' => 'success'], 200),
        ]);

        $filename = 'policy-test-' . uniqid() . '.pdf';
        $path = public_path('uploads/' . $filename);
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        $pdfBytes = '%PDF-1.4 test-policy';
        file_put_contents($path, $pdfBytes);

        try {
            SyncPolicyToAiJob::dispatchSync('UPDATE', 'vendor_policies', [
                'id' => 'pol-1',
                'document_format' => 'pdf',
                'document_url' => 'http://localhost:8000/uploads/' . $filename,
                'approved_by_admin' => true,
            ]);

            Http::assertSent(function ($request) use ($pdfBytes) {
                $record = $request['record'] ?? [];
                return ($record['document_base64'] ?? null) === base64_encode($pdfBytes);
            });
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }
}
