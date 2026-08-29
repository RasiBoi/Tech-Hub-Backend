<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\AiController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\AiPolicyController;
use Illuminate\Support\Facades\Route;

// Public Auth Routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Public Catalog Routes
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::get('/vendors', [AuthController::class, 'listApprovedVendors']);
Route::get('/vendors/{id}', [AuthController::class, 'showVendor']);

// Public AI Routes
Route::get('/ai/health', [AiController::class, 'health']);
Route::post('/ai/chat', [AiController::class, 'chat']);

// Public Promotions Route
Route::get('/promotions', [PromotionController::class, 'publicActivePromotions']);

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::get('/vendor/settings', [AuthController::class, 'getVendorSettings']);
    Route::put('/vendor/settings', [AuthController::class, 'updateVendorSettings']);
    Route::get('/vendor/followers', [AuthController::class, 'getVendorFollowers']);
    Route::post('/upload', [AuthController::class, 'uploadFile']);
    Route::get('/admin/vendors', [AuthController::class, 'listVendors']);
    Route::put('/admin/vendors/{id}/status', [AuthController::class, 'updateVendorStatus']);
    Route::post('/vendors/{id}/follow', [AuthController::class, 'toggleFollowVendor']);
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // Order / Checkout routes
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::put('/order-items/{id}/dispatch', [OrderController::class, 'dispatchItem']);
    
    // Product creation/editing (for vendors & admin)
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);

    // Policies CRUD
    Route::get('/vendor/policies', [PromotionController::class, 'getPolicies']);
    Route::post('/vendor/policies', [PromotionController::class, 'storePolicy']);
    Route::put('/vendor/policies/{id}', [PromotionController::class, 'updatePolicy']);
    Route::delete('/vendor/policies/{id}', [PromotionController::class, 'deletePolicy']);

    // Promotions CRUD
    Route::get('/vendor/promotions', [PromotionController::class, 'getPromotions']);
    Route::post('/vendor/promotions', [PromotionController::class, 'storePromotion']);
    Route::put('/vendor/promotions/{id}', [PromotionController::class, 'updatePromotion']);
    Route::delete('/vendor/promotions/{id}', [PromotionController::class, 'deletePromotion']);

    // AI dispute-system policy contract
    Route::get('/vendor/ai-policies', [AiPolicyController::class, 'vendorPolicies']);
    Route::post('/vendor/ai-policies', [AiPolicyController::class, 'storeVendorPolicy']);
    Route::put('/vendor/ai-policies/{id}', [AiPolicyController::class, 'updateVendorPolicy']);
    Route::delete('/vendor/ai-policies/{id}', [AiPolicyController::class, 'deleteVendorPolicy']);

    Route::get('/admin/vendor-policies', [AiPolicyController::class, 'adminVendorPolicies']);
    Route::put('/admin/vendor-policies/{id}/approval', [AiPolicyController::class, 'approveVendorPolicy']);
    Route::get('/admin/platform-policies', [AiPolicyController::class, 'platformPolicies']);
    Route::post('/admin/platform-policies', [AiPolicyController::class, 'storePlatformPolicy']);
});
