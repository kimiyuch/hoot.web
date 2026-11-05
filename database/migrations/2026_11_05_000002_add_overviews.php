<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shows', fn (Blueprint $table) => $table->text('overview')->nullable()->after('name'));
        Schema::table('movies', fn (Blueprint $table) => $table->text('overview')->nullable()->after('title'));
    }

    public function down(): void
    {
        Schema::table('shows', fn (Blueprint $table) => $table->dropColumn('overview'));
        Schema::table('movies', fn (Blueprint $table) => $table->dropColumn('overview'));
    }
};
