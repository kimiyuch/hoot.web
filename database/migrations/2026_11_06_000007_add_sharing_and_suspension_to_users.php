<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * shares_activity: whether what you do shows up in your followers' thread (on unless you turn it off).
     * suspended_at: set by an admin (hoot:suspend); a suspended account can't log in and is hidden everywhere.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('shares_activity')->default(true)->after('is_private');
            $table->timestamp('suspended_at')->nullable()->after('shares_activity');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['shares_activity', 'suspended_at']);
        });
    }
};
