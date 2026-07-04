<?php

namespace App\Http\Controllers;

use App\Models\Policy;
use App\Models\Promotion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    // Helpers for JSON responses
    protected function sendSuccess($data, string $message = 'Success', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $code);
    }

    protected function sendError(string $message, int $code = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null
        ], $code);
    }

    // POLICY CRUD
    public function getPolicies(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'vendor') {
            return $this->sendError('Unauthorized', 403);
        }

        $policies = Policy::where('user_id', $user->id)->orderBy('created_at', 'desc')->get();
        return $this->sendSuccess($policies, 'Policies retrieved successfully');
    }

    public function storePolicy(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'vendor') {
            return $this->sendError('Unauthorized', 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|string|in:text,pdf',
            'content' => 'nullable|string',
            'pdf_url' => 'nullable|string|max:2048',
        ]);

        $policy = Policy::create([
            'user_id' => $user->id,
            'title' => $validated['title'],
            'type' => $validated['type'],
            'content' => $validated['content'] ?? null,
            'pdf_url' => $validated['pdf_url'] ?? null,
        ]);

        return $this->sendSuccess($policy, 'Policy created successfully', 211);
    }

    public function updatePolicy(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'vendor') {
            return $this->sendError('Unauthorized', 403);
        }

        $policy = Policy::where('user_id', $user->id)->findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|string|in:text,pdf',
            'content' => 'nullable|string',
            'pdf_url' => 'nullable|string|max:2048',
        ]);

        $policy->update([
            'title' => $validated['title'],
            'type' => $validated['type'],
            'content' => $validated['content'] ?? null,
            'pdf_url' => $validated['pdf_url'] ?? null,
        ]);

        return $this->sendSuccess($policy, 'Policy updated successfully');
    }

    public function deletePolicy(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'vendor') {
            return $this->sendError('Unauthorized', 403);
        }

        $policy = Policy::where('user_id', $user->id)->findOrFail($id);
        $policy->delete();

        return $this->sendSuccess(null, 'Policy deleted successfully');
    }


    // PROMOTION CRUD
    public function getPromotions(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'vendor') {
            return $this->sendError('Unauthorized', 403);
        }

        $promotions = Promotion::where('user_id', $user->id)
            ->with('policy')
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->sendSuccess($promotions, 'Promotions retrieved successfully');
    }

    public function storePromotion(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'vendor') {
            return $this->sendError('Unauthorized', 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'image_url' => 'nullable|string|max:2048',
            'gradient' => 'nullable|string|max:255',
            'to' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
            'policy_id' => 'nullable|integer|exists:policies,id',
        ]);

        $promotion = Promotion::create([
            'user_id' => $user->id,
            'title' => $validated['title'],
            'subtitle' => $validated['subtitle'] ?? null,
            'image_url' => $validated['image_url'] ?? null,
            'gradient' => $validated['gradient'] ?? 'from-blue-500/20 via-indigo-500/10 to-cyan-400/20',
            'to' => $validated['to'] ?? '/',
            'is_active' => $validated['is_active'] ?? true,
            'policy_id' => $validated['policy_id'] ?? null,
        ]);

        return $this->sendSuccess($promotion->load('policy'), 'Promotion created successfully', 211);
    }

    public function updatePromotion(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'vendor') {
            return $this->sendError('Unauthorized', 403);
        }

        $promotion = Promotion::where('user_id', $user->id)->findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'image_url' => 'nullable|string|max:2048',
            'gradient' => 'nullable|string|max:255',
            'to' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
            'policy_id' => 'nullable|integer|exists:policies,id',
        ]);

        $promotion->update([
            'title' => $validated['title'],
            'subtitle' => $validated['subtitle'] ?? null,
            'image_url' => $validated['image_url'] ?? null,
            'gradient' => $validated['gradient'] ?? 'from-blue-500/20 via-indigo-500/10 to-cyan-400/20',
            'to' => $validated['to'] ?? '/',
            'is_active' => $validated['is_active'] ?? true,
            'policy_id' => $validated['policy_id'] ?? null,
        ]);

        return $this->sendSuccess($promotion->load('policy'), 'Promotion updated successfully');
    }

    public function deletePromotion(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'vendor') {
            return $this->sendError('Unauthorized', 403);
        }

        $promotion = Promotion::where('user_id', $user->id)->findOrFail($id);
        $promotion->delete();

        return $this->sendSuccess(null, 'Promotion deleted successfully');
    }

    // PUBLIC ENDPOINTS
    public function publicActivePromotions(Request $request): JsonResponse
    {
        $promotions = Promotion::where('is_active', true)
            ->with(['user' => function ($query) {
                $query->select('id', 'store_name', 'name');
            }, 'policy'])
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->sendSuccess($promotions, 'Active promotions retrieved successfully');
    }
}
