<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index(['role', 'status'], 'users_role_status_idx');
            $table->index(['role', 'created_at'], 'users_role_created_at_idx');
        });

        Schema::table('vendor_follows', function (Blueprint $table) {
            $table->index('vendor_id', 'vendor_follows_vendor_id_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendor_follows', function (Blueprint $table) {
            $table->dropIndex('vendor_follows_vendor_id_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_role_status_idx');
            $table->dropIndex('users_role_created_at_idx');
        });
    }
};
