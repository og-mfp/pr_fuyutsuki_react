<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diag_event', function (Blueprint $table) {
            $table->id();
            $table->uuid('reference_id')->unique();
            $table->string('error_code', 32);
            $table->unsignedSmallInteger('http_status');
            $table->string('severity', 16);
            $table->string('route_name')->nullable();
            $table->string('exception_class')->nullable();
            $table->string('summary', 160);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestampTz('occured_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diag_event');
    }
};
