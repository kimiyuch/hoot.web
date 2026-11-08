<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * cast: the first three actors' names (JSON list), for For you's Following. Shows get it from TVmaze in the
     * same request as the episodes, movies from TMDB's credits; null until the next refresh.
     */
    public function up(): void
    {
        Schema::table('shows', fn (Blueprint $table) => $table->json('cast')->nullable()->after('genres'));
        Schema::table('movies', fn (Blueprint $table) => $table->json('cast')->nullable()->after('overview'));
    }

    public function down(): void
    {
        Schema::table('shows', fn (Blueprint $table) => $table->dropColumn('cast'));
        Schema::table('movies', fn (Blueprint $table) => $table->dropColumn('cast'));
    }
};
