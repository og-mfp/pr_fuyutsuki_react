<?php

namespace App\Console\Commands;

use Illuminate\Foundation\Console\UpCommand;

class ManagedMaintenanceUp extends UpCommand
{
    protected $signature = 'maintenance:managed-up';

    protected $hidden = true;

    public function handle()
    {
        if (! app()->bound('maintenance.managed-payload')) {
            $this->error('This command must be run through maintenance:end.');

            return self::FAILURE;
        }

        return parent::handle();
    }
}
