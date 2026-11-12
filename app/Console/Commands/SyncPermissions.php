<?php

namespace App\Console\Commands;

use App\Models\Permission;
use Illuminate\Console\Command;

class SyncPermissions extends Command
{
    protected $signature = 'permissions:sync';

    protected $description = 'Synchronize application-defined permissions';

    public function handle(): int
    {
        $slugs = Permission::catalogSlugs();
        $counts = ['Created' => 0, 'Updated' => 0, 'Unchanged' => 0];

        foreach ($slugs as $slug) {
            $permission = Permission::firstOrNew(['slug' => $slug]);
            $new = ! $permission->exists;
            $permission->name = str($slug)->replace('.', ' ')->title();
            if ($new || $permission->isDirty()) {
                $permission->save();
                $counts[$new ? 'Created' : 'Updated']++;
            } else {
                $counts['Unchanged']++;
            }
        }

        $this->info('Permissions synchronized.');
        foreach ($counts as $label => $count) {
            $this->line("{$label}: {$count}");
        }
        $stale = Permission::whereNotIn('slug', $slugs)->pluck('slug');
        $this->line('Stale: '.$stale->count());
        if ($stale->isNotEmpty()) {
            $this->warn('Stale permissions: '.$stale->join(', '));
        }

        return self::SUCCESS;
    }
}
