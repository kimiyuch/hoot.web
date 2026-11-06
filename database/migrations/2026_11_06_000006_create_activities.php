<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The thread: what people did (followed a show, started or finished it, rated it, made it a favourite,
     * followed someone). One row can hold several items (following five shows in a few minutes is one
     * entry), as snapshots in `data`, so entries outlive shows and movies leaving the catalog.
     * updated_at is the last change, which is what the thread is sorted by.
     */
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->json('data');
            $table->timestamps();

            $table->index(['user_id', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
