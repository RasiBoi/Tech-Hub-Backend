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
        // Cache categories for 5 minutes — they change very rarely
        $categories = Cache::remember('categories:index:v1', 300, function () {
            return Category::query()
                ->select(['id', 'name', 'slug', 'image'])
                ->orderBy('name')
                ->get();
        });

        return $this->sendSuccess(CategoryResource::collection($categories), 'Categories retrieved successfully');
    }
}
