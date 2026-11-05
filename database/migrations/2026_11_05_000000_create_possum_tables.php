<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Followed TV shows, from TVmaze.
        Schema::create('shows', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('tvmaze_id')->unique();
            $table->string('name');
            $table->string('channel')->nullable();
            $table->string('status')->nullable();
            $table->string('image_url')->nullable();
            $table->string('url')->nullable();
            $table->timestamp('refreshed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('episodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('show_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('tvmaze_id')->unique();
            $table->unsignedInteger('season')->nullable();
            $table->unsignedInteger('number')->nullable();
            $table->string('type')->default('regular');
            $table->string('name')->nullable();
            // The exact first airing, in UTC.
            $table->timestamp('airs_at')->nullable()->index();
            $table->unsignedInteger('runtime')->nullable();
            $table->string('url')->nullable();
        });

        // Followed movies, from TMDB, with the earliest cinema and digital release anywhere.
        Schema::create('movies', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('tmdb_id')->unique();
            $table->string('title');
            $table->string('poster_path')->nullable();
            $table->date('cinema_date')->nullable()->index();
            $table->string('cinema_country', 2)->nullable();
            $table->date('digital_date')->nullable()->index();
            $table->string('digital_country', 2)->nullable();
            $table->date('release_date')->nullable();
            $table->timestamp('refreshed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('episodes');
        Schema::dropIfExists('shows');
        Schema::dropIfExists('movies');
    }
};
