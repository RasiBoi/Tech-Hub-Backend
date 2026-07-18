<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        return $user && ($user->role === 'vendor' || $user->role === 'admin');
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'old_price' => 'nullable|numeric|min:0',
            'brand' => 'nullable|string|max:255',
            'subcategory' => 'nullable|string|max:255',
            'image' => 'nullable|string',
            'images' => 'nullable|array',
            'images.*' => 'string',
            'spec' => 'nullable|string',
            'stock' => 'integer|min:0',
            'category_id' => 'required|exists:categories,id',
            'vibe' => 'nullable|string|in:walnut,minimalist,black,cyberpunk',
        ];
    }
}
