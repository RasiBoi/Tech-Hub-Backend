<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use App\Traits\ApiResponse;
use App\Models\User;
use App\Models\VendorSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

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

        $vendors = User::query()
            ->select(['id', 'name', 'email', 'role', 'store_name', 'avatar_bg', 'status', 'store_description', 'banner_url', 'created_at'])
            ->where('role', 'vendor')
            ->with('vendorSetting')
            ->withCount(['followers', 'products'])
            ->withAvg('products', 'rating')
            ->latest()
            ->get();

        $request->attributes->set('followed_vendor_ids', []);

        return $this->sendSuccess(UserResource::collection($vendors), 'Vendors retrieved successfully');
    }

    public function listApprovedVendors(Request $request): JsonResponse
    {
        $authUser = $request->user('sanctum');

        // Public vendor directory — cache the fully resolved payload for unauthenticated visitors.
        if (!$authUser) {
            $cachedPayload = Cache::remember('vendors:approved:public:v2', 300, function () use ($request) {
                $vendors = User::query()
                    ->select(['id', 'name', 'email', 'role', 'store_name', 'avatar_bg', 'status', 'store_description', 'banner_url', 'created_at'])
                    ->where('role', 'vendor')
                    ->where('status', 'approved')
                    ->with('vendorSetting')       // eager-load to prevent N+1
                    ->withCount(['followers', 'products'])
                    ->withAvg('products', 'rating')
                    ->orderBy('store_name')
                    ->get();

                $request->attributes->set('followed_vendor_ids', []);

                return UserResource::collection($vendors)->resolve($request);
            });

            return $this->sendSuccess($cachedPayload, 'Approved vendors retrieved successfully');
        }

        // Authenticated request — cache per user for 60 seconds.
        $cacheKey = 'vendors:approved:user:' . $authUser->id . ':v2';
        $cached = Cache::remember($cacheKey, 60, function () use ($authUser, $request) {
            $vendors = User::query()
                ->select(['id', 'name', 'email', 'role', 'store_name', 'avatar_bg', 'status', 'store_description', 'banner_url', 'created_at'])
                ->where('role', 'vendor')
                ->where('status', 'approved')
                ->with('vendorSetting')           // eager-load to prevent N+1
                ->withCount(['followers', 'products'])
                ->withAvg('products', 'rating')
                ->orderBy('store_name')
                ->get();

            $followedVendorIds = $authUser->followedVendors()
                ->pluck('users.id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $request->attributes->set('followed_vendor_ids', $followedVendorIds);

            return UserResource::collection($vendors)->resolve($request);
        });

        return $this->sendSuccess($cached, 'Approved vendors retrieved successfully');
    }

    public function showVendor(Request $request, $id): JsonResponse
    {
        $authUser = $request->user('sanctum');
        $userId   = $authUser ? $authUser->id : 0;

        // Cache the vendor profile for 120 seconds, keyed by vendor id + viewer id
        $cacheKey = "vendor:show:{$id}:user:{$userId}:v2";

        $resolved = Cache::remember($cacheKey, 120, function () use ($id, $authUser, $request) {
            $vendor = User::query()
                ->select(['id', 'name', 'email', 'role', 'store_name', 'avatar_bg', 'status', 'store_description', 'banner_url', 'created_at'])
                ->where('role', 'vendor')
                ->where('status', 'approved')
                ->with('vendorSetting')           // eager-load to prevent N+1
                ->withCount(['followers', 'products'])
                ->withAvg('products', 'rating')
                ->findOrFail($id);

            $followedVendorIds = [];
            if ($authUser && $authUser->followedVendors()->where('vendor_id', $vendor->id)->exists()) {
                $followedVendorIds[] = (int) $vendor->id;
            }

            $request->attributes->set('followed_vendor_ids', $followedVendorIds);

            return (new UserResource($vendor))->resolve($request);
        });

        return $this->sendSuccess($resolved, 'Vendor profile retrieved successfully');
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

    public function getVendorSettings(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'vendor') {
            return $this->sendError('Unauthorized', 403);
        }

        $settings = VendorSetting::firstOrCreate([
            'user_id' => $user->id,
        ], [
            'shop_theme' => 'element',
            'policy_type' => 'text',
        ]);

        return $this->sendSuccess($settings, 'Vendor settings retrieved successfully');
    }

    public function updateVendorSettings(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'vendor') {
            return $this->sendError('Unauthorized', 403);
        }

        $validated = $request->validate([
            'cover_image_url' => 'nullable|string|max:2048',
            'logo_url' => 'nullable|string|max:2048',
            'shop_theme' => 'nullable|string|max:255',
            'company_profile' => 'nullable|string|max:10000',
            'policy_type' => 'nullable|string|in:text,pdf',
            'policy_text' => 'nullable|string|max:20000',
            'policy_pdf_url' => 'nullable|string|max:2048',
        ]);

        $settings = VendorSetting::updateOrCreate(
            ['user_id' => $user->id],
            $validated
        );

        // Also sync the banner_url and store_description on users table for backwards compatibility
        if (isset($validated['cover_image_url'])) {
            $user->update(['banner_url' => $validated['cover_image_url']]);
        }
        if (isset($validated['company_profile'])) {
            $user->update(['store_description' => $validated['company_profile']]);
        }

        return $this->sendSuccess($settings, 'Vendor settings updated successfully');
    }

    public function getVendorFollowers(Request $request): JsonResponse
    {
        $vendor = $request->user();
        if ($vendor->role !== 'vendor') {
            return $this->sendError('Unauthorized', 403);
        }

        $followers = $vendor->followers()
            ->select(['users.id', 'users.name', 'users.email', 'users.avatar_bg', 'vendor_follows.created_at'])
            ->get();

        return $this->sendSuccess($followers, 'Followers retrieved successfully');
    }

    public function uploadFile(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:jpeg,png,jpg,gif,svg,pdf,txt|max:10240', // max 10MB
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            
            $destinationPath = public_path('uploads');
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }
            
            $file->move($destinationPath, $filename);
            $url = url('uploads/' . $filename);

            return $this->sendSuccess([
                'url' => $url,
                'filename' => $filename,
            ], 'File uploaded successfully');
        }

        return $this->sendError('No file uploaded', 400);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return $this->sendSuccess(null, 'Logged out successfully');
    }
}
