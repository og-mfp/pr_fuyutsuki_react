<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('app_settings')) {
            return;
        }

        foreach (['registration_mode' => 'public', 'email_confirmation_required' => false] as $setting => $default) {
            $oldKey = 'authentication.'.$setting;
            $newKey = 'users.'.$setting;
            $old = DB::table('app_settings')->where('key', $oldKey)->first();
            if (! $old) {
                continue;
            }

            if (! DB::table('app_settings')->where('key', $newKey)->exists()) {
                $value = json_decode($old->value, true) ?? $default;
                if ($setting === 'registration_mode' && $value === 'disabled') {
                    $value = 'administrator_created';
                }
                DB::table('app_settings')->insert([
                    'key' => $newKey,
                    'value' => json_encode($value),
                    'updated_by' => $old->updated_by,
                    'created_at' => $old->created_at,
                    'updated_at' => $old->updated_at,
                ]);
            }

            DB::table('app_settings')->where('key', $oldKey)->delete();
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('app_settings')) {
            return;
        }

        foreach (['registration_mode', 'email_confirmation_required'] as $setting) {
            $newKey = 'users.'.$setting;
            $oldKey = 'authentication.'.$setting;
            $new = DB::table('app_settings')->where('key', $newKey)->first();
            if ($new && ! DB::table('app_settings')->where('key', $oldKey)->exists()) {
                $value = json_decode($new->value, true);
                if ($setting === 'registration_mode' && $value === 'administrator_created') {
                    $value = 'disabled';
                }
                DB::table('app_settings')->insert([
                    'key' => $oldKey,
                    'value' => json_encode($value),
                    'updated_by' => $new->updated_by,
                    'created_at' => $new->created_at,
                    'updated_at' => $new->updated_at,
                ]);
            }
            DB::table('app_settings')->where('key', $newKey)->delete();
        }
    }
};
