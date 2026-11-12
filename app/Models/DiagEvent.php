<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/** @property CarbonImmutable $occured_at */
#[Fillable(['reference_id', 'error_code', 'http_status', 'severity', 'route_name', 'exception_class', 'summary', 'user_id', 'occured_at'])]
class DiagEvent extends Model
{
    protected $table = 'diag_event';

    public $timestamps = false;

    protected function casts(): array
    {
        return ['occured_at' => 'immutable_datetime'];
    }
}
