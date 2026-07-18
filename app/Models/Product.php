<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable([
    'title',
    'description',
    'price',
    'old_price',
    'brand',
    'subcategory',
    'image',
    'images',
    'spec',
    'stock',
    'rating',
    'reviews_count',
    'category_id',
    'vendor_id',
    'vibe'
])]
class Product extends Model
{
    protected $casts = [
        'images' => 'array',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }
}
