<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $user_id
 * @property string $action
 * @property string $status
 * @property string $session_hash
 * @property Carbon $expires_at
 * @property Carbon $report_expires_at
 */
class SecurityChallenge extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['user_id' => 'integer', 'attempts' => 'integer', 'expires_at' => 'datetime', 'report_expires_at' => 'datetime'];
    }
}
