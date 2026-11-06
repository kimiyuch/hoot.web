<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Where you are with a show or movie, and what you thought of it. Independent of following and favourites.
     * rating is in half stars: 1 = ½ star … 10 = 5 stars.
     */
    public function up(): void
    {
        foreach (['show_user', 'movie_user'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                // plan, watching, paused, watched, dropped (movies: plan, watched); null = none.
                $table->string('status', 10)->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('finished_at')->nullable();
                $table->unsignedTinyInteger('rating')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['show_user', 'movie_user'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropColumn(['status', 'started_at', 'finished_at', 'rating']));
        }
    }
};
