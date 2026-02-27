<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('glimpse_events', function (Blueprint $table) {
            $table->id();

            // May be null if event is dispatched outside of a tracked request
            // (e.g. from a queued job). Callers can pass a session hash explicitly.
            $table->string('session_hash')->nullable()->index();
            $table->string('name')->index();

            // Arbitrary key/value payload supplied by the developer.
            $table->json('properties')->nullable();
            $table->timestamps();

            $table->index(['name', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('glimpse_events');
    }
};
