<?php

namespace App\Http\Controllers;

use App\Models\PlatformPolicy;
use App\Models\VendorPolicy;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

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

        $validated = $request->validate([
            'policy_name' => 'required|string|max:255',
            'policy_type' => 'required|string|max:100',
            'max_return_days' => 'nullable|integer|min:0|max:365',
            'refund_type' => 'nullable|string|max:100',
            'restocking_fee_percent' => 'nullable|numeric|min:0|max:100',
            'conditions' => 'nullable|array',
        ]);

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
        $validated = $request->validate([
            'policy_name' => 'required|string|max:255',
            'policy_type' => 'required|string|max:100',
            'max_return_days' => 'nullable|integer|min:0|max:365',
            'refund_type' => 'nullable|string|max:100',
            'restocking_fee_percent' => 'nullable|numeric|min:0|max:100',
            'conditions' => 'nullable|array',
        ]);

        $policy->update([
            ...$validated,
            'approved_by_admin' => false,
            'approved_at' => null,
            'approved_by_user_id' => null,
        ]);

        return $this->sendSuccess($policy, 'Vendor AI policy updated and returned to pending approval');
    }

    public function deleteVendorPolicy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'vendor') {
            return $this->sendError('Unauthorized', 403);
        }

        $policy = VendorPolicy::where('vendor_user_id', $user->id)->findOrFail($id);
        $policy->delete();

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

        if ($policy->approved_by_admin) {
            $this->syncPolicyToAi($policy);
        }

        return $this->sendSuccess($policy->fresh(), 'Vendor AI policy approval updated successfully');
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

        return $this->sendSuccess($policy, 'Platform policy saved successfully', 201);
    }

    private function syncPolicyToAi(VendorPolicy $policy): void
    {
        $webhookUrl = config('services.ai.policy_sync_webhook_url');
        if (!$webhookUrl) {
            return;
        }

        Http::timeout(5)->post($webhookUrl, [
            'type' => 'UPDATE',
            'table' => 'vendor_policies',
            'record' => [
                'id' => $policy->id,
                'vendor_id' => $policy->vendor_id,
                'policy_name' => $policy->policy_name,
                'policy_type' => $policy->policy_type,
                'max_return_days' => $policy->max_return_days,
                'refund_type' => $policy->refund_type,
                'restocking_fee_percent' => $policy->restocking_fee_percent,
                'conditions' => $policy->conditions,
                'approved_by_admin' => $policy->approved_by_admin,
            ],
        ]);
    }
}
