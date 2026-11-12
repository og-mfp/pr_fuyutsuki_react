<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;

class AuditRelationships
{
    /** @param array<int> $before
     * @param  array<int>  $after
     */
    public static function record(Model $subject, string $relation, array $before, array $after): void
    {
        sort($before);
        sort($after);
        if ($before === $after) {
            return;
        }

        activity('authorization')->performedOn($subject)->event($relation.'.synced')
            ->withProperties(['relation' => $relation, 'old' => $before, 'attributes' => $after])
            ->log($relation.' changed');
    }
}
