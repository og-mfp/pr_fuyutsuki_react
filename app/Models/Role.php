<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Role extends Model
{
    use LogsActivity;

    protected $table = 'rbac_roles';

    protected $fillable = ['group_id', 'name', 'slug', 'description'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('authorization')
            ->logOnly(['group_id', 'name', 'slug', 'description'])->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    /** @return BelongsTo<Group, $this> */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /** @return BelongsToMany<Permission, $this> */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permission');
    }
}
