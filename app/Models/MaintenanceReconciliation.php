<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property CarbonImmutable $observed_at
 * @property list<int> $registered_operation_ids
 */
#[Fillable(['actual_active', 'native_operation_id', 'native_event_id', 'native_reason', 'registered_operation_ids', 'explanation', 'operator_identifier', 'reconciled_by', 'observed_at'])]
class MaintenanceReconciliation extends Model
{
    protected function casts(): array
    {
        return ['actual_active' => 'boolean', 'native_operation_id' => 'integer', 'native_event_id' => 'integer', 'registered_operation_ids' => 'array', 'observed_at' => 'immutable_datetime'];
    }

    /** @return BelongsTo<User, $this> */
    public function reconciler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reconciled_by');
    }
}
