<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('glimpse_page_views', function (Blueprint $table) {
            $table->id();
            $table->string('session_hash')->index();

            // Full URL stored for debugging; path used for grouping in queries.
            $table->text('url')->nullable();
            $table->string('path', 2048)->nullable();

            // Query string stored separately so paths can be grouped cleanly.
            $table->string('query_string')->nullable();

            // The referrer at the page-view level (may differ from session referrer
            // if the visitor navigated from an external link mid-session).
            $table->string('referrer')->nullable();

            // How long the visitor spent on THIS page before the next request.
            // Null until the next page view arrives (or session expires).
            $table->unsignedInteger('time_on_page_seconds')->nullable();

            $table->timestamps();
            $table->index(['created_at']);
            $table->index(['path', 'created_at']);

            $table->foreign('session_hash')
                ->references('session_hash')
                ->on('glimpse_sessions')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('glimpse_page_views');
    }
};
