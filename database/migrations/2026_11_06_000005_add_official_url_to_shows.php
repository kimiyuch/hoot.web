<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** TVmaze's "officialSite": usually the show's page on its streaming service or network. */
    public function up(): void
    {
        Schema::table('shows', fn (Blueprint $table) => $table->string('official_url')->nullable());
    }

    public function down(): void
    {
        Schema::table('shows', fn (Blueprint $table) => $table->dropColumn('official_url'));
    }
};
