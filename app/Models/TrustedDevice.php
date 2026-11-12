<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property Carbon $first_seen_at
 * @property Carbon $last_seen_at
 * @property Carbon $expires_at
 * @property Carbon|null $revoked_at
 */
class TrustedDevice extends Model
{
    protected $fillable = ['user_id', 'token_hash', 'name', 'first_seen_at', 'last_seen_at', 'expires_at', 'revoked_at'];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return ['first_seen_at' => 'datetime', 'last_seen_at' => 'datetime', 'expires_at' => 'datetime', 'revoked_at' => 'datetime'];
    }
}
