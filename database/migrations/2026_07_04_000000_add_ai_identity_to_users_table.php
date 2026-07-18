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

        Schema::table('users', function (Blueprint $table) {
            $table->uuid('ai_uuid')->nullable()->unique()->after('id');
        });

        DB::table('users')
            ->whereNull('ai_uuid')
            ->update(['ai_uuid' => DB::raw('gen_random_uuid()')]);

        DB::statement('ALTER TABLE users ALTER COLUMN ai_uuid SET NOT NULL');
        DB::statement('ALTER TABLE users ALTER COLUMN ai_uuid SET DEFAULT gen_random_uuid()');
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['ai_uuid']);
            $table->dropColumn('ai_uuid');
        });
    }
};
