<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_windows', function (Blueprint $table) {
            $table->id();
            $table->string('title', 160);
            $table->string('type', 40);
            $table->text('reason');
            $table->text('public_message');
            $table->timestampTz('scheduled_start_at');
            $table->timestampTz('scheduled_end_at');
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('ended_at')->nullable();
            $table->string('status', 20)->default('scheduled');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('started_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('deployment_ref', 160)->nullable();
            $table->text('operator_notes')->nullable();
            $table->boolean('published')->default(true);
            $table->timestampsTz();
            $table->index(['status', 'scheduled_start_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_windows');
    }
};
