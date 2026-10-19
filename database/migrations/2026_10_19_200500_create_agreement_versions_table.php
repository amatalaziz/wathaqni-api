<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agreement_versions', function (Blueprint $table) {
            $table->id();
            $table->morphs('agreeable');
            $table->unsignedInteger('version');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            // Immutable snapshot: amount, currency, parties, schedules and written terms.
            $table->json('terms');
            $table->enum('agreement_status', ['pending', 'accepted', 'rejected', 'superseded'])->default('pending');
            $table->foreignId('creditor_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('debtor_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('creditor_accepted_at')->nullable();
            $table->timestamp('debtor_accepted_at')->nullable();
            $table->unique(['agreeable_type', 'agreeable_id', 'version'], 'agreement_versions_subject_version_unique');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agreement_versions');
    }
};
