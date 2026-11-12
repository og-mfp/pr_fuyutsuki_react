<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Config;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Permission extends Model
{
    use LogsActivity;

    protected $fillable = ['name', 'slug', 'description'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('authorization')
            ->logOnly(['name', 'slug', 'description'])->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    /** @return list<string> */
    public static function catalogSlugs(): array
    {
        $slugs = [];
        foreach (Config::array('permissions') as $entries) {
            if (! is_array($entries)) {
                continue;
            }
            foreach ($entries as $slug) {
                if (is_string($slug)) {
                    $slugs[] = $slug;
                }
            }
        }

        return $slugs;
    }

    /** @return BelongsToMany<Role, $this> */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permission');
    }
}
