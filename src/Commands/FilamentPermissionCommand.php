<?php

namespace Recca0120\FilamentPermission\Commands;

use Illuminate\Console\Command;

class FilamentPermissionCommand extends Command
{
    public $signature = 'filament-permission';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
