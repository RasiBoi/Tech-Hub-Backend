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
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->text('image_url')->nullable();
            $table->string('gradient')->default('from-blue-500/20 via-indigo-500/10 to-cyan-400/20');
            $table->string('to')->default('/');
            $table->boolean('is_active')->default(true);
            $table->foreignId('policy_id')->nullable()->constrained('policies')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
