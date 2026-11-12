<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_windows', function (Blueprint $table) {
            $table->timestampTz('recovery_observed_at')->nullable();
            $table->timestampTz('closed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_windows', function (Blueprint $table) {
            $table->dropColumn(['recovery_observed_at', 'closed_at']);
        });
    }
};
