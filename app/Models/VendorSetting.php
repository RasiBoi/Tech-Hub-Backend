<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'cover_image_url',
        'logo_url',
        'shop_theme',
        'company_profile',
        'policy_type',
        'policy_text',
        'policy_pdf_url',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
