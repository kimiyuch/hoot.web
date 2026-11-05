<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shows', function (Blueprint $table) {
            // Set when the show was followed from Browse (TMDB), so Browse can show it as followed.
            $table->unsignedInteger('tmdb_id')->nullable()->after('tvmaze_id');
            $table->decimal('rating', 3, 1)->nullable()->after('status');
            $table->json('genres')->nullable()->after('rating');
            $table->string('image_large_url')->nullable()->after('image_url');
        });

        Schema::table('movies', function (Blueprint $table) {
            $table->decimal('rating', 3, 1)->nullable()->after('title');
            $table->string('backdrop_path')->nullable()->after('poster_path');
            // Keys from config('possum.services') that have the movie to stream in at least one country.
            $table->json('services')->nullable()->after('backdrop_path');
        });
    }

    public function down(): void
    {
        Schema::table('shows', fn (Blueprint $table) => $table->dropColumn(['tmdb_id', 'rating', 'genres', 'image_large_url']));
        Schema::table('movies', fn (Blueprint $table) => $table->dropColumn(['rating', 'backdrop_path', 'services']));
    }
};
