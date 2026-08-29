<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dispute extends Model
{
    use HasUuids;

    protected $fillable = [
        'id',
        'dispute_number',
        'order_id',
        'customer_id',
        'type',
        'status',
        'decision',
        'complaint_text',
        'refund_amount',
        'currency',
        'evidence_urls',
        'resolution_notes',
        'customer_notes',
    ];

    protected $casts = [
        'evidence_urls' => 'array',
        'refund_amount' => 'float',
    ];

    protected $keyType = 'string';
    public $incrementing = false;

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
