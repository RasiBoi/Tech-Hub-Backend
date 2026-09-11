<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

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

    /**
     * PgBouncer + emulated prepares interpolates PHP false as integer 0,
     * which Postgres rejects on boolean columns.
     */
    public static function pgsqlBool(bool $value): \Illuminate\Contracts\Database\Query\Expression
    {
        return DB::raw($value ? 'true' : 'false');
    }

    public function setApproval(bool $approved, ?int $adminUserId = null): void
    {
        DB::table($this->getTable())->where('id', $this->getKey())->update([
            'approved_by_admin' => static::pgsqlBool($approved),
            'approved_at' => $approved ? now() : null,
            'approved_by_user_id' => $approved ? $adminUserId : null,
            'updated_at' => now(),
        ]);
        $this->refresh();
    }

    /**
     * Shop Customize "Store Policies" live in vendor_settings. Admin AI approval
     * reads vendor_policies, so persist a pending storefront row whenever the vendor saves text/PDF.
     */
    public static function syncFromStoreSettings(User $user, VendorSetting $settings): ?self
    {
        $policyType = $settings->policy_type === 'pdf' ? 'pdf' : 'text';
        $text = trim((string) ($settings->policy_text ?? ''));
        $pdfUrl = trim((string) ($settings->policy_pdf_url ?? ''));
        $hasText = $policyType === 'text' && $text !== '';
        $hasPdf = $policyType === 'pdf' && $pdfUrl !== '';

        $existing = static::query()
            ->where('vendor_user_id', $user->id)
            ->where('policy_type', 'storefront')
            ->first();

        if (!$hasText && !$hasPdf) {
            if ($existing) {
                $existing->delete();
            }
            return null;
        }

        $documentFormat = $hasPdf ? 'pdf' : 'text';
        $policyBody = $hasText ? $text : null;
        $documentUrl = $hasPdf ? $pdfUrl : null;
        $contentChanged = !$existing
            || $existing->document_format !== $documentFormat
            || (string) $existing->policy_body !== (string) $policyBody
            || (string) $existing->document_url !== (string) $documentUrl;

        $attributes = [
            'vendor_id' => $user->ai_uuid,
            'vendor_user_id' => $user->id,
            'policy_name' => trim(($user->store_name ?: $user->name) . ' store policy'),
            'policy_type' => 'storefront',
            'max_return_days' => $existing->max_return_days ?? 14,
            'refund_type' => $existing->refund_type ?? 'store_credit',
            'restocking_fee_percent' => $existing->restocking_fee_percent ?? 0,
            'conditions' => $existing->conditions ?? [
                'requires_original_packaging' => true,
                'requires_purchase_proof' => true,
            ],
            'document_format' => $documentFormat,
            'policy_body' => $policyBody,
            'document_url' => $documentUrl,
        ];

        if ($existing) {
            if ($contentChanged) {
                $attributes['approved_by_admin'] = static::pgsqlBool(false);
                $attributes['approved_at'] = null;
                $attributes['approved_by_user_id'] = null;
            }
            $existing->update($attributes);
            return $existing->fresh();
        }

        return static::create($attributes);
    }
}
