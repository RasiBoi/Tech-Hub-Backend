<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Traits\ApiResponse;
use App\Http\Resources\CategoryResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class CategoryController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        // Cache categories for 5 minutes — they change very rarely
        $cacheKey = 'categories:index:v1';
        $categories = Cache::remember($cacheKey, 300, function () {
            return Category::query()
                ->select(['id', 'name', 'slug', 'image'])
                ->orderBy('name')
                ->get();
        });

        if (!$categories instanceof Collection) {
            Cache::forget($cacheKey);
            $categories = Category::query()
                ->select(['id', 'name', 'slug', 'image'])
                ->orderBy('name')
                ->get();

            Cache::put($cacheKey, $categories, 300);
        }

        return $this->sendSuccess(CategoryResource::collection($categories), 'Categories retrieved successfully');
    }
}
