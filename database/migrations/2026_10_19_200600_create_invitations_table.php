<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            $table->morphs('invitable');
            $table->foreignId('invited_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('invitee_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('invitee_phone', 16);
            $table->string('invitee_role', 32);
            // SHA-256 of a cryptographically random token; never store the raw token.
            $table->char('token_hash', 64)->unique();
            $table->timestamp('expires_at')->index();
            $table->enum('invitation_status', ['pending', 'accepted', 'rejected', 'revoked', 'expired'])->default('pending');
            $table->timestamp('responded_at')->nullable();
            $table->index(['invitee_phone', 'invitation_status']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};
