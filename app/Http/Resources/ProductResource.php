<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $images = $this->normalizedImages();
        $isDetail = (bool) $request->route('id');

        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'vendor_id' => $this->vendor_id,
            'title' => $this->title,
            'description' => $this->description,
            'price' => (float) $this->price,
            'old_price' => $this->old_price ? (float) $this->old_price : null,
            'brand' => $this->brand,
            'subcategory' => $this->subcategory,
            'image' => $this->image,
            'images' => $request->route('id') ? $images : null,
            'images_count' => count($images),
            'spec' => $this->spec,
            'stock' => (int) $this->stock,
            'rating' => (float) $this->rating,
            'reviews_count' => (int) $this->reviews_count,
            'vibe' => $this->vibe,
            'category' => $this->whenLoaded('category', fn () => $this->categorySummary()),
            'vendor' => $this->whenLoaded('vendor', fn () => $isDetail ? new UserResource($this->vendor) : $this->vendorSummary()),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    private function normalizedImages(): array
    {
        $raw = $this->resource->getRawOriginal('images');

        if (is_array($raw)) {
            return array_values(array_filter($raw));
        }

        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);

            if (is_array($decoded)) {
                return array_values(array_filter($decoded));
            }
        }

        $castValue = $this->resource->getAttribute('images');

        return is_array($castValue) ? array_values(array_filter($castValue)) : [];
    }

    private function categorySummary(): ?array
    {
        if (!$this->resource->relationLoaded('category') || !$this->category) {
            return null;
        }

        return [
            'id' => $this->category->id,
            'name' => $this->category->name,
            'slug' => $this->category->slug,
            'image' => $this->category->image,
        ];
    }

    private function vendorSummary(): ?array
    {
        if (!$this->resource->relationLoaded('vendor') || !$this->vendor) {
            return null;
        }

        return [
            'id' => $this->vendor->id,
            'ai_uuid' => $this->vendor->ai_uuid,
            'name' => $this->vendor->name,
            'email' => $this->vendor->email,
            'role' => $this->vendor->role,
            'store_name' => $this->vendor->store_name,
            'avatar_bg' => $this->vendor->avatar_bg,
            'status' => $this->vendor->status,
            'store_description' => $this->vendor->store_description,
            'banner_url' => $this->vendor->banner_url,
        ];
    }
}
