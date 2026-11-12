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
            $table->string('language', 2)->default('en');
            $table->string('timezone')->default('UTC');
            $table->string('date_format', 10)->default('Y-m-d');
            $table->string('time_format', 10)->default('H:i');
            $table->string('number_format', 12)->default('1,234.56');
            $table->string('currency', 3)->default('USD');
            $table->string('currency_display', 10)->default('symbol');
        });

        if (Schema::hasTable('app_settings')) {
            $preferences = [];
            foreach (DB::table('app_settings')->whereIn('key', [
                'localization.language', 'localization.timezone', 'localization.date_format', 'localization.time_format',
                'localization.number_format', 'localization.currency', 'localization.currency_display',
            ])->get(['key', 'value']) as $setting) {
                $preferences[substr($setting->key, strlen('localization.'))] = json_decode($setting->value, true);
            }
            if ($preferences !== []) {
                DB::table('users')->update($preferences);
            }
        }
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn([
            'language', 'timezone', 'date_format', 'time_format', 'number_format', 'currency', 'currency_display',
        ]));
    }
};
