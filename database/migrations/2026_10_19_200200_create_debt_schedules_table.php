<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('debt_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('debt_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('sequence');
            $table->decimal('amount', 19, 4);
            $table->date('due_date');
            $table->unique(['debt_id', 'sequence']);
            $table->unique(['id', 'debt_id']);
            $table->index(['debt_id', 'due_date']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debt_schedules');
    }
};
