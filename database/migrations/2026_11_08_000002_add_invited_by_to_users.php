<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * invited_by_id: whose invite link someone signed up through. They follow that person once they've
     * confirmed their email (App\Services\Invites). Null when nobody invited them or the inviter is gone.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('invited_by_id')->nullable()->after('discover_hidden')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('invited_by_id');
        });
    }
};
