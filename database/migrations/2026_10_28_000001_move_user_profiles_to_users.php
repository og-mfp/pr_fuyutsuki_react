<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('full_name')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->text('address')->nullable();
            $table->string('primary_phone', 20)->nullable();
            $table->string('secondary_phone', 20)->nullable();
        });

        DB::table('user_profiles')->orderBy('user_id')->each(function ($profile) {
            DB::table('users')->where('id', $profile->user_id)->update([
                'full_name' => $profile->full_name,
                'date_of_birth' => $profile->date_of_birth,
                'address' => $profile->address,
                'primary_phone' => $profile->primary_phone,
                'secondary_phone' => $profile->secondary_phone,
            ]);
        });

        Schema::drop('user_profiles');
    }

    public function down(): void
    {
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('full_name')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->text('address')->nullable();
            $table->string('primary_phone', 20)->nullable();
            $table->string('secondary_phone', 20)->nullable();
            $table->timestamps();
        });

        DB::table('users')->where(fn ($query) => $query->whereNotNull('full_name')->orWhereNotNull('date_of_birth')
            ->orWhereNotNull('address')->orWhereNotNull('primary_phone')->orWhereNotNull('secondary_phone'))
            ->orderBy('id')->each(function ($user) {
                DB::table('user_profiles')->insert([
                    'user_id' => $user->id,
                    'full_name' => $user->full_name,
                    'date_of_birth' => $user->date_of_birth,
                    'address' => $user->address,
                    'primary_phone' => $user->primary_phone,
                    'secondary_phone' => $user->secondary_phone,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ]);
            });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['full_name', 'date_of_birth', 'address', 'primary_phone', 'secondary_phone']);
        });
    }
};
