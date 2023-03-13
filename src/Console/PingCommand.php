<?php declare(strict_types=1);

namespace Pinepain\SystemInfo\Console;


use Illuminate\Console\Command;


class PingCommand extends Command
{
    protected $signature = 'system-info:ping';

    protected $description = 'Basic command that output "pong" and exit';

    public function handle()
    {
        $this->info('pong');

        return 0;
    }

}
