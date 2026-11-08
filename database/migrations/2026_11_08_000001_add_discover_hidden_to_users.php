<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * discover_hidden: what someone never wants to see in Discover, as keys of hoot.hideable
     * (["anime", "korean", "indian"]). Null means nothing is hidden.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('discover_hidden')->nullable()->after('episode_search_url');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('discover_hidden');
        });
    }
};
