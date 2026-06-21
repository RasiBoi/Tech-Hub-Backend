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
            'image' => 'nullable|string',
            'spec' => 'nullable|string',
            'stock' => 'integer|min:0',
            'category_id' => 'required|exists:categories,id',
            'vibe' => 'nullable|string|in:walnut,minimalist,black,cyberpunk',
        ];
    }
}
