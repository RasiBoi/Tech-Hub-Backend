<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // If the user is not a vendor, skip vendor-specific DB queries (followers, products, rating) to avoid N+1 queries.
        if ($this->role !== 'vendor') {
            return [
                'id' => $this->id,
                'name' => $this->name,
                'email' => $this->email,
                'role' => $this->role,
                'store_name' => $this->store_name,
                'avatar_bg' => $this->avatar_bg,
                'status' => $this->status,
                'store_description' => $this->store_description ?? 'Premium workspace accessories & gear.',
                'banner_url' => $this->banner_url ?? 'https://images.unsplash.com/photo-1512316609839-ce289d3eba0a?q=80&w=1368&auto=format&fit=crop',
                'followers_count' => 0,
                'rating' => 0,
                'products_count' => 0,
                'is_followed' => false,
                'created_at' => $this->created_at?->toIso8601String(),
            ];
        }

        $followedVendorIds = $request->attributes->get('followed_vendor_ids');
        
        if ($followedVendorIds === null) {
            $authUser = $request->user('sanctum');
            if ($authUser) {
                $followedVendorIds = $authUser->followedVendors()
                    ->pluck('users.id')
                    ->map(fn ($id) => (int) $id)
                    ->all();
            } else {
                $followedVendorIds = [];
            }
            $request->attributes->set('followed_vendor_ids', $followedVendorIds);
        }

        $isFollowed = in_array((int) $this->id, $followedVendorIds, true);

        $followersCount = array_key_exists('followers_count', $this->getAttributes())
            ? (int) $this->followers_count
            : ($this->relationLoaded('followers') ? $this->followers->count() : $this->followers()->count());

        $productsCount = array_key_exists('products_count', $this->getAttributes())
            ? (int) $this->products_count
            : ($this->relationLoaded('products') ? $this->products->count() : $this->products()->count());

        $rating = array_key_exists('products_avg_rating', $this->getAttributes())
            ? ($this->products_avg_rating !== null ? round((float) $this->products_avg_rating, 1) : 5.0)
            : $this->rating;

        $settings = $this->vendorSetting;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'store_name' => $this->store_name,
            'avatar_bg' => $this->avatar_bg,
            'status' => $this->status,
            'store_description' => $this->store_description ?? 'Premium workspace accessories & gear.',
            'banner_url' => $this->banner_url ?? 'https://images.unsplash.com/photo-1512316609839-ce289d3eba0a?q=80&w=1368&auto=format&fit=crop',
            'followers_count' => $followersCount,
            'rating' => $rating,
            'products_count' => $productsCount,
            'is_followed' => $isFollowed,
            'created_at' => $this->created_at?->toIso8601String(),
            // Inline vendor settings
            'logo_url' => $settings?->logo_url ?? '',
            'cover_image_url' => $settings?->cover_image_url ?? $this->banner_url ?? 'https://images.unsplash.com/photo-1512316609839-ce289d3eba0a?q=80&w=1368&auto=format&fit=crop',
            'shop_theme' => $settings?->shop_theme ?? 'element',
            'company_profile' => $settings?->company_profile ?? $this->store_description ?? 'Premium workspace accessories & gear.',
            'policy_type' => $settings?->policy_type ?? 'text',
            'policy_text' => $settings?->policy_text ?? '',
            'policy_pdf_url' => $settings?->policy_pdf_url ?? '',
        ];
    }
}
