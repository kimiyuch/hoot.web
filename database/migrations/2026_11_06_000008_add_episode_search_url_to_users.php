<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * episode_search_url: a search link you set up yourself, with {query} where the episode goes
     * ("ted lasso s04e03"). Hoot shows it next to episodes that are out; empty means no link.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('episode_search_url', 500)->nullable()->after('shares_activity');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('episode_search_url');
        });
    }
};
