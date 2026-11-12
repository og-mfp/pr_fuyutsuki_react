<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection(config('activitylog.database_connection'))->table(config('activitylog.table_name'), function (Blueprint $table) {
            $table->index(['log_name', 'created_at']);
            $table->index(['event', 'created_at']);
            $table->index(['causer_type', 'causer_id', 'created_at']);
            $table->index(['subject_type', 'subject_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::connection(config('activitylog.database_connection'))->table(config('activitylog.table_name'), function (Blueprint $table) {
            $table->dropIndex(['log_name', 'created_at']);
            $table->dropIndex(['event', 'created_at']);
            $table->dropIndex(['causer_type', 'causer_id', 'created_at']);
            $table->dropIndex(['subject_type', 'subject_id', 'created_at']);
        });
    }
};
