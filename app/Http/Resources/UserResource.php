<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $authUser = $request->user('sanctum');
        $isFollowed = false;
        if ($authUser) {
            $isFollowed = $this->followers()->where('user_id', $authUser->id)->exists();
        }

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
            'followers_count' => $this->followers_count,
            'rating' => $this->rating,
            'products_count' => $this->products()->count(),
            'is_followed' => $isFollowed,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
