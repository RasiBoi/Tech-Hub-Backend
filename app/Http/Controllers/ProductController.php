<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductStoreRequest;
use App\Http\Requests\ProductUpdateRequest;
use App\Http\Resources\ProductResource;
use App\Services\ProductService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use ApiResponse;

    protected ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function index(Request $request): JsonResponse
    {
        $products = $this->productService->getFilteredProducts($request->all());

        return $this->sendSuccess(
            ProductResource::collection($products),
            'Products retrieved successfully'
        );
    }

    public function show($id): JsonResponse
    {
        $product = $this->productService->getProduct($id);

        if (!$product) {
            return $this->sendError('Product not found', 404);
        }

        return $this->sendSuccess(new ProductResource($product), 'Product retrieved successfully');
    }

    public function store(ProductStoreRequest $request): JsonResponse
    {
        $product = $this->productService->createProduct(
            $request->validated(),
            $request->user()
        );

        $product->load(['category', 'vendor']);

        return $this->sendSuccess(new ProductResource($product), 'Product created successfully', 201);
    }

    public function update(ProductUpdateRequest $request, $id): JsonResponse
    {
        $success = $this->productService->updateProduct($id, $request->validated());

        if (!$success) {
            return $this->sendError('Product update failed or product not found', 400);
        }

        $product = $this->productService->getProduct($id);

        return $this->sendSuccess(new ProductResource($product), 'Product updated successfully');
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        // Check manual permission
        $product = $this->productService->getProduct($id);

        if (!$product) {
            return $this->sendError('Product not found', 404);
        }

        $user = $request->user();
        if ($user->role !== 'admin' && $product->vendor_id !== $user->id) {
            return $this->sendError('This action is unauthorized.', 403);
        }

        $this->productService->deleteProduct($id);

        return $this->sendSuccess(null, 'Product deleted successfully');
    }
}
