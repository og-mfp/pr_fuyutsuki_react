<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('security_recovery_required')->nullable();
        });

        Schema::create('security_challenges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('action');
            $table->string('notification_email');
            $table->string('target')->nullable();
            $table->string('payload_hash')->nullable();
            $table->string('session_hash');
            $table->string('code_hash')->nullable();
            $table->string('status')->default('pending');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('report_expires_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_challenges');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('security_recovery_required'));
    }
};
