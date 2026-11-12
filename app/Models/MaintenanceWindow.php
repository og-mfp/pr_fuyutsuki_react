<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property CarbonImmutable $scheduled_start_at
 * @property CarbonImmutable $scheduled_end_at
 * @property CarbonImmutable|null $started_at
 * @property CarbonImmutable|null $ended_at
 * @property CarbonImmutable|null $recovery_observed_at
 * @property CarbonImmutable|null $closed_at
 */
#[Fillable(['title', 'type', 'reason', 'public_message', 'scheduled_start_at', 'scheduled_end_at', 'status', 'created_by', 'started_by', 'completed_by', 'started_by_operator', 'completed_by_operator', 'deployment_ref', 'operator_notes', 'published', 'started_at', 'ended_at', 'recovery_observed_at', 'closed_at'])]
class MaintenanceWindow extends Model
{
    protected function casts(): array
    {
        return ['scheduled_start_at' => 'immutable_datetime', 'scheduled_end_at' => 'immutable_datetime', 'started_at' => 'immutable_datetime', 'ended_at' => 'immutable_datetime', 'recovery_observed_at' => 'immutable_datetime', 'closed_at' => 'immutable_datetime', 'published' => 'boolean'];
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function starter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    /** @return BelongsTo<User, $this> */
    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
