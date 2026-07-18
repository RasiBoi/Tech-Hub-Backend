<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (!$user) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        // Check if the current user is the owner of the product
        $productId = $this->route('id');
        $product = \App\Models\Product::find($productId);
        
        return $product && $product->vendor_id === $user->id;
    }

    public function rules(): array
    {
        return [
            'title' => 'string|max:255',
            'description' => 'nullable|string',
            'price' => 'numeric|min:0',
            'old_price' => 'nullable|numeric|min:0',
            'brand' => 'nullable|string|max:255',
            'subcategory' => 'nullable|string|max:255',
            'image' => 'nullable|string',
            'images' => 'nullable|array',
            'images.*' => 'string',
            'spec' => 'nullable|string',
            'stock' => 'integer|min:0',
            'category_id' => 'exists:categories,id',
            'vibe' => 'nullable|string|in:walnut,minimalist,black,cyberpunk',
        ];
    }
}
