<?php

namespace App\Http\Controllers;

use App\Jobs\SyncPolicyToAiJob;
use App\Models\PlatformPolicy;
use App\Models\VendorPolicy;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AiPolicyController extends Controller
{
    use ApiResponse;

    public function vendorPolicies(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'vendor') {
            return $this->sendError('Unauthorized', 403);
        }

        $policies = VendorPolicy::where('vendor_user_id', $user->id)
            ->latest()
            ->get();

        return $this->sendSuccess($policies, 'Vendor AI policies retrieved successfully');
    }

    public function storeVendorPolicy(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'vendor') {
            return $this->sendError('Unauthorized', 403);
        }

        $validated = $this->validateVendorPolicyInput($request);

        $policy = VendorPolicy::create([
            ...$validated,
            'vendor_id' => $user->ai_uuid,
            'vendor_user_id' => $user->id,
            'approved_by_admin' => false,
        ]);

        return $this->sendSuccess($policy, 'Vendor AI policy submitted for admin approval', 201);
    }

    public function updateVendorPolicy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'vendor') {
            return $this->sendError('Unauthorized', 403);
        }

        $policy = VendorPolicy::where('vendor_user_id', $user->id)->findOrFail($id);
        $wasApproved = (bool) $policy->approved_by_admin;
        $validated = $this->validateVendorPolicyInput($request);

        $policy->update([
            ...$validated,
            'approved_by_admin' => false,
            'approved_at' => null,
            'approved_by_user_id' => null,
        ]);

        if ($wasApproved) {
            $this->dispatchPolicySync('UPDATE', 'vendor_policies', $policy->fresh(), remove: true);
        }

        return $this->sendSuccess($policy->fresh(), 'Vendor AI policy updated and returned to pending approval');
    }

    public function deleteVendorPolicy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'vendor') {
            return $this->sendError('Unauthorized', 403);
        }

        $policy = VendorPolicy::where('vendor_user_id', $user->id)->findOrFail($id);
        $record = $this->vendorPolicyPayload($policy);
        $wasApproved = (bool) $policy->approved_by_admin;
        $policy->delete();

        if ($wasApproved) {
            $this->dispatchPolicySync('DELETE', 'vendor_policies', null, $record);
        }

        return $this->sendSuccess(null, 'Vendor AI policy deleted successfully');
    }

    public function adminVendorPolicies(Request $request): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return $this->sendError('Unauthorized', 403);
        }

        $policies = VendorPolicy::with(['vendor:id,name,email,store_name,ai_uuid', 'approvedBy:id,name,email'])
            ->latest()
            ->get();

        return $this->sendSuccess($policies, 'Vendor AI policies retrieved successfully');
    }

    public function approveVendorPolicy(Request $request, string $id): JsonResponse
    {
        $admin = $request->user();
        if ($admin->role !== 'admin') {
            return $this->sendError('Unauthorized', 403);
        }

        $validated = $request->validate([
            'approved_by_admin' => 'required|boolean',
        ]);

        $policy = VendorPolicy::findOrFail($id);
        $policy->update([
            'approved_by_admin' => $validated['approved_by_admin'],
            'approved_at' => $validated['approved_by_admin'] ? now() : null,
            'approved_by_user_id' => $validated['approved_by_admin'] ? $admin->id : null,
        ]);

        $fresh = $policy->fresh();
        if ($fresh->approved_by_admin) {
            $this->dispatchPolicySync('UPDATE', 'vendor_policies', $fresh);
        } else {
            $this->dispatchPolicySync('UPDATE', 'vendor_policies', $fresh, remove: true);
        }

        return $this->sendSuccess($fresh, 'Vendor AI policy approval updated successfully');
    }

    public function platformPolicies(Request $request): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return $this->sendError('Unauthorized', 403);
        }

        return $this->sendSuccess(PlatformPolicy::orderBy('policy_key')->get(), 'Platform policies retrieved successfully');
    }

    public function storePlatformPolicy(Request $request): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return $this->sendError('Unauthorized', 403);
        }

        $validated = $request->validate([
            'policy_key' => 'required|string|max:255',
            'policy_name' => 'required|string|max:255',
            'max_value' => 'nullable|numeric',
            'min_value' => 'nullable|numeric',
            'is_mandatory' => 'nullable|boolean',
        ]);

        $policy = PlatformPolicy::updateOrCreate(
            ['policy_key' => $validated['policy_key']],
            [
                'policy_name' => $validated['policy_name'],
                'max_value' => $validated['max_value'] ?? null,
                'min_value' => $validated['min_value'] ?? null,
                'is_mandatory' => $validated['is_mandatory'] ?? true,
            ]
        );

        $this->dispatchPolicySync('UPDATE', 'platform_policies', $policy);

        return $this->sendSuccess($policy, 'Platform policy saved successfully', 201);
    }

    private function dispatchPolicySync(
        string $type,
        string $table,
        VendorPolicy|PlatformPolicy|null $policy = null,
        ?array $recordOverride = null,
        bool $remove = false,
    ): void {
        if ($recordOverride !== null) {
            $record = $recordOverride;
        } elseif ($policy instanceof VendorPolicy) {
            $record = $this->vendorPolicyPayload($policy);
            if ($remove) {
                $record['approved_by_admin'] = false;
            }
        } elseif ($policy instanceof PlatformPolicy) {
            $record = $this->platformPolicyPayload($policy);
        } else {
            return;
        }

        // Prefer sync so local/staging works without a queue worker.
        // Failures are logged inside the job; HTTP approve still succeeds.
        try {
            SyncPolicyToAiJob::dispatchSync(
                $type,
                $table,
                $record,
                null,
                (string) Str::uuid(),
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('AI policy sync dispatch failed', [
                'error' => $e->getMessage(),
                'table' => $table,
                'type' => $type,
            ]);
            // Fall back to queued retry when a worker is available
            SyncPolicyToAiJob::dispatch(
                $type,
                $table,
                $record,
                null,
                (string) Str::uuid(),
            );
        }
    }

    private function vendorPolicyPayload(VendorPolicy $policy): array
    {
        return [
            'id' => $policy->id,
            'vendor_id' => $policy->vendor_id,
            'policy_name' => $policy->policy_name,
            'policy_type' => $policy->policy_type,
            'max_return_days' => $policy->max_return_days,
            'refund_type' => $policy->refund_type,
            'restocking_fee_percent' => $policy->restocking_fee_percent,
            'conditions' => $policy->conditions,
            'document_format' => $policy->document_format,
            'policy_body' => $policy->policy_body,
            'document_url' => $policy->document_url,
            'approved_by_admin' => (bool) $policy->approved_by_admin,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validateVendorPolicyInput(Request $request): array
    {
        $validated = $request->validate([
            'policy_name' => 'required|string|max:255',
            'policy_type' => 'required|string|max:100',
            'max_return_days' => 'nullable|integer|min:0|max:365',
            'refund_type' => 'nullable|string|max:100',
            'restocking_fee_percent' => 'nullable|numeric|min:0|max:100',
            'conditions' => 'nullable|array',
            'document_format' => 'nullable|string|in:text,markdown,pdf',
            'policy_body' => 'nullable|string|max:50000',
            'document_url' => 'nullable|string|url|max:2048',
        ]);

        $format = $validated['document_format'] ?? null;
        if ($format === null || $format === '') {
            $validated['document_format'] = null;
            $validated['policy_body'] = null;
            $validated['document_url'] = null;
            return $validated;
        }

        if (in_array($format, ['text', 'markdown'], true)) {
            $body = trim((string) ($validated['policy_body'] ?? ''));
            if ($body === '') {
                throw ValidationException::withMessages([
                    'policy_body' => ['Policy document body is required for text or markdown format.'],
                ]);
            }
            $validated['policy_body'] = $body;
            $validated['document_url'] = null;
            return $validated;
        }

        if ($format === 'pdf') {
            $url = trim((string) ($validated['document_url'] ?? ''));
            if ($url === '') {
                throw ValidationException::withMessages([
                    'document_url' => ['Document URL is required for PDF format.'],
                ]);
            }
            $validated['document_url'] = $url;
            $validated['policy_body'] = null;
        }

        return $validated;
    }

    private function platformPolicyPayload(PlatformPolicy $policy): array
    {
        return [
            'id' => $policy->id,
            'policy_key' => $policy->policy_key,
            'policy_name' => $policy->policy_name,
            'max_value' => $policy->max_value,
            'min_value' => $policy->min_value,
            'is_mandatory' => (bool) $policy->is_mandatory,
        ];
    }
}
