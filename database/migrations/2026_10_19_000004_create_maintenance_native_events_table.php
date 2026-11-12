<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_native_events', function (Blueprint $table) {
            $table->id();
            $table->string('command', 10);
            $table->unsignedSmallInteger('exit_code');
            $table->boolean('actual_active')->nullable();
            $table->unsignedBigInteger('native_operation_id')->nullable();
            $table->text('native_reason')->nullable();
            $table->json('registered_operation_ids')->nullable();
            $table->timestampTz('observed_at');
            $table->timestampsTz();
            $table->index(['command', 'observed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_native_events');
    }
};
