<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });
        Schema::create('group_user', function (Blueprint $table) {
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unique(['group_id', 'user_id']);
        });
        Schema::create('group_permission', function (Blueprint $table) {
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->unique(['group_id', 'permission_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_permission');
        Schema::dropIfExists('group_user');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('groups');
    }
};
