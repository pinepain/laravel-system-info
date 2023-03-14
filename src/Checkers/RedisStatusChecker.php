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

    public function check(mixed ...$args): Result
    {
        $failFast = $args['failFast'] ?? true;

        $checks = [];
        $healthy = true;

        $connectionsConfig = config('database.redis');

        unset($connectionsConfig['client']);
        unset($connectionsConfig['options']);
        unset($connectionsConfig['clusters']); // yup, no clustering support atm

        foreach (array_keys($connectionsConfig) as $connection) {
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
            Log::warning("Redis connection '{$connection}' status check failed", ['connection' => $connection, 'e' => $e->getMessage(), 'class' => get_class($e), 'trace' => $e->getTraceAsString()]);
        }

        return false;
    }
}
