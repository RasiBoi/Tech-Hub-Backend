<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PlatformPolicy extends Model
{
    use HasUuids;

    protected $fillable = [
        'id',
        'policy_key',
        'policy_name',
        'max_value',
        'min_value',
        'is_mandatory',
    ];

    protected $casts = [
        'max_value' => 'float',
        'min_value' => 'float',
        'is_mandatory' => 'boolean',
    ];

    protected $keyType = 'string';
    public $incrementing = false;
}
