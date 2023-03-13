<?php declare(strict_types=1);

namespace Pinepain\SystemInfo\Checkers;


use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Throwable;


class RedisStatusChecker implements CheckerInterface
{
    public function getName(): string
    {
        return 'redis';
    }

    public function check(bool $failFast = true): Result
    {
        $checks = [];
        $healthy = true;

        $connectionsConfig = config('database.redis');

        unset($connectionsConfig['client']);
        unset($connectionsConfig['options']);
        unset($connectionsConfig['clusters']); // yup, no clustering support atm, feels free to open PR or sound out the use case

        foreach ($connectionsConfig as $connection => $config) {
            $checks[$connection] = $this->checkConnection($connection);
            $healthy = $healthy && $checks[$connection];

            if (!$healthy && $failFast) {
                break;
            }
        }

        return new Result($healthy, $checks);
    }

    private function checkConnection(string $connection): bool
    {
        try {
            Redis::connection($connection)->command('ping');

            return true;
        } catch (Throwable $e) {
            Log::error("Redis connection '{$connection}' status check failed", ['connection' => $connection, 'e' => $e->getMessage(), 'class' => get_class($e), 'trace' => $e->getTraceAsString()]);
        }

        return false;
    }
}
