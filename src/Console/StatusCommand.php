<?php declare(strict_types=1);

namespace Pinepain\SystemInfo\Console;


use Illuminate\Console\Command;
use Pinepain\SystemInfo\Checkers\AggregateStatusChecker;
use Pinepain\SystemInfo\Checkers\Result;


class StatusCommand extends Command
{
    protected $signature = 'system-info:status {components?* : Individual component names to check }
            {--fast : Fail fast on first faulty component}
            {--strict : Run checks in a strict mode so even optional one would cause failure}
    ';

    protected $description = 'Output app status';

    public function handle(AggregateStatusChecker $checker)
    {
        $res = $checker->check(
            failFast: $this->option('fast'),
            strict: $this->option('strict'),
            components: $this->argument('components'),
        );

        if (!$res->isHealthy()) {
            $this->error('FAIL', 'quiet');
        } else {
            $this->info('OK');
        }

        foreach ($res->getDetails() as $component => $details) {
            if (!($details instanceof Result)) {
                $this->warn("  {$component} - unexpected status");
                continue;
            }

            if (!$details->isHealthy()) {
                $this->error("  {$component}: FAIL", 'v');
            } else {
                $this->info("  {$component}: OK", 'vv');
            }
            foreach ($details->getDetails() as $sub => $status) {
                if (!$status) {
                    $this->error("    {$sub}: FAIL", 'v');
                } else {
                    $this->line("    {$sub}: OK", 'fg=gray', 'vvv');
                }
            }
        }

        return (int)!$res->isHealthy();
    }

}
