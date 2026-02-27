<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('glimpse_aggregates', function (Blueprint $table) {
            $table->id();
            $table->string('period');
            $table->date('date')->index();
            $table->unsignedTinyInteger('hour')->nullable();

            // What are we measuring? e.g. 'visitors', 'page_views',
            // 'sessions', 'bounce_rate', 'avg_duration'.
            $table->string('metric')->index();

            // Optional breakdown dimension e.g. 'country:GB', 'browser:Chrome',
            // 'path:/about', 'channel:organic', 'event:signup'.
            $table->string('dimension')->nullable()->index();

            // The aggregate value (count, rate, sum depending on metric).
            $table->decimal('value')->default(0);

            // Raw count of rows that contributed to this aggregate bucket.
            $table->unsignedBigInteger('count')->default(0);
            $table->timestamp('aggregated_at')->useCurrent();

            // One row per (period, date, hour, metric, dimension) combination.
            $table->unique(['period', 'date', 'hour', 'metric', 'dimension']);
            $table->index(['period', 'date', 'metric']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('glimpse_aggregates');
    }
};
