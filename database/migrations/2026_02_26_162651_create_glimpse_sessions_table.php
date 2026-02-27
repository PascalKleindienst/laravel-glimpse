<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('glimpse_sessions', function (Blueprint $table) {
            $table->id();
            // Anonymous identity — a hash of the Laravel session ID.
            // Never stores PII; ephemeral by nature (tied to session lifetime).
            $table->string('session_hash')->unique();

            // Hashed IP for deduplication across sessions when needed,
            // but never stored in raw form.
            $table->string('ip_hash')->nullable()->index();

            // Geo
            $table->string('country_code', 2)->nullable();
            $table->string('region', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('language', 16)->nullable(); // e.g. "en", "en-US"

            // Device / Browser
            $table->string('browser', 64)->nullable();
            $table->string('browser_version', 20)->nullable();
            $table->string('os', 64)->nullable();
            $table->string('os_version', 20)->nullable();
            $table->string('platform')->nullable();

            // Referrer
            $table->string('referrer_url')->nullable();
            $table->string('referrer_domain')->nullable();
            $table->string('referrer_channel')->nullable()->index();

            // Journey
            $table->string('entry_page', 2048)->nullable();
            $table->string('exit_page', 2048)->nullable();

            // Aggregated counters (updated on every hit)
            $table->unsignedSmallInteger('page_view_count')->default(1);
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->boolean('is_bounce')->default(true);

            $table->timestamp('started_at');
            $table->timestamp('last_seen_at');

            // No standard created_at/updated_at; we manage timestamps manually
            // for clarity and performance.
            $table->index(['started_at']);
            $table->index(['last_seen_at']);
            $table->index(['country_code', 'startet_at']);
            $table->index(['platform', 'startet_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('glimpse_sessions');
    }
};
