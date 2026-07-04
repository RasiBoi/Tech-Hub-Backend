<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'role', 'store_name', 'avatar_bg', 'status', 'store_description', 'banner_url'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'vendor_id');
    }

    public function vendorSetting(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(VendorSetting::class, 'user_id');
    }

    public function followedVendors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'vendor_follows', 'user_id', 'vendor_id')->withTimestamps();
    }

    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'vendor_follows', 'vendor_id', 'user_id')->withTimestamps();
    }

    public function promotions(): HasMany
    {
        return $this->hasMany(Promotion::class);
    }

    public function policies(): HasMany
    {
        return $this->hasMany(Policy::class);
    }

    public function getFollowersCountAttribute(): int
    {
        if ($this->relationLoaded('followers')) {
            return $this->followers->count();
        }
        return $this->followers()->count();
    }

    public function getRatingAttribute(): float
    {
        if ($this->relationLoaded('products')) {
            $avg = $this->products->avg('rating');
        } else {
            $avg = $this->products()->avg('rating');
        }
        return $avg !== null ? round((float)$avg, 1) : 5.0;
    }
}
