<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->boolean('actual_active');
            $table->unsignedBigInteger('native_operation_id')->nullable();
            $table->unsignedBigInteger('native_event_id')->nullable();
            $table->text('native_reason')->nullable();
            $table->json('registered_operation_ids');
            $table->text('explanation');
            $table->string('operator_identifier', 160)->nullable();
            $table->foreignId('reconciled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('observed_at');
            $table->timestampsTz();
            $table->index(['actual_active', 'observed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_reconciliations');
    }
};
