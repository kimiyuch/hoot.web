<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Profiles: a handle for /@username, a short bio, and whether the profile is private.
     * Existing users have no username yet and are asked for one on their next visit.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 20)->nullable()->unique();
            $table->string('bio', 160)->nullable();
            $table->boolean('is_private')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['username', 'bio', 'is_private']));
    }
};
