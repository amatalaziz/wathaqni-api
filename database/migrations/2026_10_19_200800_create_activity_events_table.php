<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_events', function (Blueprint $table) {
            $table->id();
            $table->morphs('subject');
            $table->foreignId('actor_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('event', 64);
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at');
            $table->index(['subject_type', 'subject_id', 'occurred_at'], 'activity_events_subject_time_index');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_events');
    }
};
