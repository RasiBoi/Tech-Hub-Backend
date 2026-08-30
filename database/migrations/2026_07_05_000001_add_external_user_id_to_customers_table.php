<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bridge customers for AI OrderTool phone lookup + keep id = users.ai_uuid.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('customers', 'external_user_id')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->text('external_user_id')->nullable()->unique()->after('email');
            });
        }

        if (Schema::hasColumn('customers', 'user_id') && Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
                UPDATE customers c
                SET id = u.ai_uuid
                FROM users u
                WHERE c.user_id = u.id
                  AND u.ai_uuid IS NOT NULL
                  AND c.id IS DISTINCT FROM u.ai_uuid
                  AND NOT EXISTS (
                      SELECT 1 FROM customers c2 WHERE c2.id = u.ai_uuid AND c2.user_id <> c.user_id
                  )
            SQL);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('customers', 'external_user_id')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropUnique(['external_user_id']);
                $table->dropColumn('external_user_id');
            });
        }
    }
};
