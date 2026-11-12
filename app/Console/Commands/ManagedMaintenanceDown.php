<?php

namespace App\Console\Commands;

use Illuminate\Foundation\Console\DownCommand;

class ManagedMaintenanceDown extends DownCommand
{
    protected $signature = 'maintenance:managed-down {--redirect=} {--render=} {--retry=} {--refresh=} {--secret=} {--with-secret} {--status=503}';

    protected $hidden = true;

    /** @return array<string, mixed> */
    protected function getDownFilePayload()
    {
        if (! app()->bound('maintenance.managed-payload')) {
            throw new \RuntimeException('This command must be run through maintenance:begin.');
        }

        return array_replace(parent::getDownFilePayload(), app('maintenance.managed-payload'));
    }
}
