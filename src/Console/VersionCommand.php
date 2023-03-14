<?php declare(strict_types=1);

namespace Pinepain\SystemInfo\Console;


use Illuminate\Console\Command;
use Pinepain\SystemInfo\Checkers\VersionChecker;


class VersionCommand extends Command
{
    protected $signature = 'system-info:version';

    protected $description = 'Output app version and related info';

    public function handle(VersionChecker $checker)
    {
        $res = $checker->check();

        foreach ($res->getDetails() as $k => $v) {
            $this->info("{$k} = {$v}");
        }

        return (int)!$res->isHealthy();
    }
}
