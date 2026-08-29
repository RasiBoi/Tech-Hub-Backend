<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS "pgcrypto"');

        Schema::create('platform_policies', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->text('policy_key')->unique();
            $table->text('policy_name');
            $table->decimal('max_value', 12, 2)->nullable();
            $table->decimal('min_value', 12, 2)->nullable();
            $table->boolean('is_mandatory')->default(true);
            $table->timestampsTz();
        });

        Schema::create('vendor_policies', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('vendor_id');
            $table->foreignId('vendor_user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->text('policy_name');
            $table->text('policy_type');
            $table->integer('max_return_days')->nullable();
            $table->text('refund_type')->nullable();
            $table->decimal('restocking_fee_percent', 5, 2)->nullable();
            $table->jsonb('conditions')->nullable();
            $table->boolean('approved_by_admin')->default(false);
            $table->timestampTz('approved_at')->nullable();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->foreign('vendor_id')->references('ai_uuid')->on('users')->cascadeOnDelete();
            $table->index(['vendor_id', 'policy_type']);
            $table->index(['approved_by_admin', 'policy_type']);
        });

        DB::statement('ALTER TABLE platform_policies ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE vendor_policies ENABLE ROW LEVEL SECURITY');
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_policies');
        Schema::dropIfExists('platform_policies');
    }
};
