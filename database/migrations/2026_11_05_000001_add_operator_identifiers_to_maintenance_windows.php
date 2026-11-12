<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_windows', function (Blueprint $table) {
            $table->string('started_by_operator', 160)->nullable();
            $table->string('completed_by_operator', 160)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_windows', function (Blueprint $table) {
            $table->dropColumn(['started_by_operator', 'completed_by_operator']);
        });
    }
};
