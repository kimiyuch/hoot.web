<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A row in show_user / movie_user now means "in your library". Following is one flag of it,
     * being a favourite another, so a favourite-only classic isn't followed (or refreshed).
     */
    public function up(): void
    {
        foreach (['show_user', 'movie_user'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->boolean('following')->default(true);
                // Null: not a favourite. 1, 2, 3 …: its place among your favourites.
                $table->unsignedTinyInteger('favourite_position')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['show_user', 'movie_user'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropColumn(['following', 'favourite_position']));
        }
    }
};
