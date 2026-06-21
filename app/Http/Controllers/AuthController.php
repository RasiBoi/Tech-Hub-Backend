<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    use ApiResponse;

    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return $this->sendSuccess([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ], 'User registered successfully', 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(
            $request->input('email'),
            $request->input('password')
        );

        return $this->sendSuccess([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ], 'User logged in successfully');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->sendSuccess(new UserResource($request->user()), 'User profile retrieved');
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'name' => 'string|max:255',
            'store_name' => 'nullable|string|max:255',
            'avatar_bg' => 'nullable|string|max:255',
            'store_description' => 'nullable|string|max:2000',
            'banner_url' => 'nullable|string|max:2048',
        ]);

        $user->update($validated);

        return $this->sendSuccess(new UserResource($user), 'Profile updated successfully');
    }

    public function listVendors(Request $request): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return $this->sendError('Unauthorized', 403);
        }
        $vendors = \App\Models\User::where('role', 'vendor')->latest()->get();
        return $this->sendSuccess(UserResource::collection($vendors), 'Vendors retrieved successfully');
    }

    public function listApprovedVendors(Request $request): JsonResponse
    {
        $vendors = \App\Models\User::where('role', 'vendor')
            ->where('status', 'approved')
            ->get();
        return $this->sendSuccess(UserResource::collection($vendors), 'Approved vendors retrieved successfully');
    }

    public function showVendor(Request $request, $id): JsonResponse
    {
        $vendor = \App\Models\User::where('role', 'vendor')
            ->where('status', 'approved')
            ->findOrFail($id);
        return $this->sendSuccess(new UserResource($vendor), 'Vendor profile retrieved successfully');
    }

    public function toggleFollowVendor(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $vendor = \App\Models\User::where('role', 'vendor')
            ->where('status', 'approved')
            ->findOrFail($id);

        $isFollowing = $user->followedVendors()->where('vendor_id', $vendor->id)->exists();

        if ($isFollowing) {
            $user->followedVendors()->detach($vendor->id);
            $following = false;
            $msg = "Unfollowed store successfully";
        } else {
            $user->followedVendors()->attach($vendor->id);
            $following = true;
            $msg = "Followed store successfully";
        }

        return $this->sendSuccess([
            'is_followed' => $following,
            'followers_count' => $vendor->followers()->count()
        ], $msg);
    }

    public function updateVendorStatus(Request $request, $id): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return $this->sendError('Unauthorized', 403);
        }
        $request->validate([
            'status' => 'required|string|in:approved,pending,rejected'
        ]);

        $vendor = \App\Models\User::findOrFail($id);
        if ($vendor->role !== 'vendor') {
            return $this->sendError('User is not a vendor', 400);
        }

        $vendor->update(['status' => $request->input('status')]);

        return $this->sendSuccess(new UserResource($vendor), 'Vendor status updated successfully');
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return $this->sendSuccess(null, 'Logged out successfully');
    }
}
