<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Traits\ApiResponse;
use App\Http\Resources\CategoryResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class CategoryController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $cacheKey = 'categories:index:v1';

        // Cache as a plain array to avoid Eloquent-Collection deserialization
        // issues that cause the cache to be bypassed on every request.
        $cached = Cache::remember($cacheKey, 300, function () {
            return Category::query()
                ->select(['id', 'name', 'slug', 'image'])
                ->orderBy('name')
                ->get()
                ->toArray();
        });

        // Re-hydrate so CategoryResource::collection() works unchanged.
        $categories = is_array($cached)
            ? Category::hydrate($cached)
            : $cached;

        return $this->sendSuccess(CategoryResource::collection($categories), 'Categories retrieved successfully');
    }
}

