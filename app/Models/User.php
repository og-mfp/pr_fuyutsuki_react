<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $avatar_path
 * @property Carbon|null $date_of_birth
 * @property Carbon|null $email_verified_at
 * @property CarbonInterface|null $frozen_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property int $maintenance_session_version
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password', 'full_name', 'date_of_birth', 'address', 'primary_phone', 'secondary_phone', 'language', 'timezone', 'date_format', 'time_format', 'number_format', 'currency', 'currency_display'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    /** @return Attribute<string, string> */
    protected function email(): Attribute
    {
        return Attribute::make(set: fn (string $email): string => Str::lower($email));
    }

    protected $attributes = [
        'language' => 'en', 'timezone' => 'UTC', 'date_format' => 'Y-m-d', 'time_format' => 'H:i',
        'number_format' => '1,234.56', 'currency' => 'USD', 'currency_display' => 'symbol',
    ];

    /** @use HasFactory<UserFactory> */
    use HasFactory, LogsActivity, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('users')
            ->logOnly(['name', 'email', 'email_verified_at', 'frozen_at'])
            ->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    /** @return BelongsToMany<Group, $this> */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class);
    }

    /** @return HasMany<TrustedDevice, $this> */
    public function trustedDevices(): HasMany
    {
        return $this->hasMany(TrustedDevice::class);
    }

    public function hasPermission(string $slug): bool
    {
        return ! $this->frozen_at && in_array($slug, Permission::catalogSlugs(), true)
            && $this->groups()->whereHas('roles.permissions', fn ($query) => $query->where('slug', $slug))->exists();
    }

    /** @return list<string> */
    public function permissionSlugs(): array
    {
        return $this->frozen_at ? [] : $this->assignedPermissionSlugs();
    }

    /** @return list<string> */
    public function assignedPermissionSlugs(): array
    {
        return array_values(Permission::query()->whereIn('slug', Permission::catalogSlugs())->whereHas('roles.group.users', fn ($query) => $query->whereKey($this->getKey()))
            ->distinct()->pluck('slug')->map(fn (string $slug): string => $slug)->all());
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'date_of_birth' => 'date',
            'frozen_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }
}
