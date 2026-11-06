<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Shows and movies become a shared catalog; who follows what lives in two pivot tables.
     * Follows from before accounts belonged to nobody, so they stay in the catalog unfollowed.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // The secret in each user's calendar feed URL.
            $table->string('calendar_token', 64)->nullable()->unique();
        });

        Schema::create('show_user', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('show_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();
            $table->primary(['user_id', 'show_id']);
        });

        Schema::create('movie_user', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('movie_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();
            $table->primary(['user_id', 'movie_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movie_user');
        Schema::dropIfExists('show_user');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('calendar_token'));
    }
};
