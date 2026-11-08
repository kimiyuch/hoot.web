<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * weekly_digest: a mail on Monday mornings with what comes out that week and who followed you
     * (hoot:digest). Off unless switched on in Settings.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('weekly_digest')->default(false)->after('invited_by_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('weekly_digest');
        });
    }
};
