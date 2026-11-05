<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('debug:test')]
#[Description('Prueba de depuración Xdebug')]
class DebugTest extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $nombre = 'Laravel 13';

        $version = 'PHP 8.4';

        $resultado = $nombre . ' - ' . $version;

        $this->info($resultado);

        return self::SUCCESS;
    }
}
