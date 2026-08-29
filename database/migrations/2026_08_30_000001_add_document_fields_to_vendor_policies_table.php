<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_policies', function (Blueprint $table) {
            $table->text('document_format')->nullable()->after('conditions');
            $table->text('policy_body')->nullable()->after('document_format');
            $table->text('document_url')->nullable()->after('policy_body');
        });
    }

    public function down(): void
    {
        Schema::table('vendor_policies', function (Blueprint $table) {
            $table->dropColumn(['document_format', 'policy_body', 'document_url']);
        });
    }
};
