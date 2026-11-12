<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property CarbonImmutable $observed_at
 * @property list<int>|null $registered_operation_ids
 */
#[Fillable(['command', 'exit_code', 'actual_active', 'native_operation_id', 'native_reason', 'registered_operation_ids', 'observed_at'])]
class MaintenanceNativeEvent extends Model
{
    protected function casts(): array
    {
        return ['actual_active' => 'boolean', 'native_operation_id' => 'integer', 'registered_operation_ids' => 'array', 'observed_at' => 'immutable_datetime'];
    }
}
