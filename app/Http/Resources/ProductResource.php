<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'price' => (float) $this->price,
            'old_price' => $this->old_price ? (float) $this->old_price : null,
            'image' => $this->image,
            'spec' => $this->spec,
            'stock' => (int) $this->stock,
            'rating' => (float) $this->rating,
            'reviews_count' => (int) $this->reviews_count,
            'vibe' => $this->vibe,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'vendor' => new UserResource($this->whenLoaded('vendor')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
