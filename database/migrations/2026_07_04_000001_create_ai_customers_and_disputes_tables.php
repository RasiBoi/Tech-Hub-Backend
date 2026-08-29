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

        Schema::create('customers', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->text('name')->nullable();
            $table->text('email')->nullable()->unique();
            $table->text('tier')->default('standard');
            $table->integer('total_orders')->default(0);
            $table->integer('dispute_count')->default(0);
            $table->timestampsTz();
        });

        DB::statement(<<<'SQL'
            INSERT INTO customers (id, user_id, name, email, tier, total_orders, dispute_count, created_at, updated_at)
            SELECT
                ai_uuid,
                id,
                name,
                email,
                'standard',
                0,
                0,
                COALESCE(created_at, now()),
                now()
            FROM users
            WHERE role = 'customer'
            ON CONFLICT (email) DO NOTHING
        SQL);

        Schema::create('disputes', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->text('dispute_number')->unique();
            $table->text('order_id');
            $table->uuid('customer_id')->nullable();
            $table->text('type')->nullable();
            $table->text('status')->default('submitted');
            $table->text('decision')->default('PENDING');
            $table->text('complaint_text')->nullable();
            $table->decimal('refund_amount', 12, 2)->nullable();
            $table->text('currency')->default('USD');
            $table->jsonb('evidence_urls')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->text('customer_notes')->nullable();
            $table->timestampsTz();

            $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
            $table->index('order_id');
            $table->index(['customer_id', 'status']);
        });

        DB::statement('ALTER TABLE customers ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE disputes ENABLE ROW LEVEL SECURITY');
    }

    public function down(): void
    {
        Schema::dropIfExists('disputes');
        Schema::dropIfExists('customers');
    }
};
