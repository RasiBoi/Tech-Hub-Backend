<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorPolicy extends Model
{
    use HasUuids;

    protected $fillable = [
        'id',
        'vendor_id',
        'vendor_user_id',
        'policy_name',
        'policy_type',
        'max_return_days',
        'refund_type',
        'restocking_fee_percent',
        'conditions',
        'document_format',
        'policy_body',
        'document_url',
        'approved_by_admin',
        'approved_at',
        'approved_by_user_id',
    ];

    protected $casts = [
        'conditions' => 'array',
        'approved_by_admin' => 'boolean',
        'approved_at' => 'datetime',
        'restocking_fee_percent' => 'float',
    ];

    protected $keyType = 'string';
    public $incrementing = false;

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_user_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }
}
