<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Group extends Model
{
    use LogsActivity;

    protected $fillable = ['name', 'slug', 'description'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('authorization')
            ->logOnly(['name', 'slug', 'description'])->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    /** @return BelongsToMany<User, $this> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /** @return HasMany<Role, $this> */
    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    public function defaultRole(): Role
    {
        return $this->roles()->firstOrCreate(['slug' => 'default'], ['name' => 'Default']);
    }
}
