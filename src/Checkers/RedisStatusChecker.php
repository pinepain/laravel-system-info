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
        $isStrict = $args['strict'] ?? false;

        $checks = [];
        $healthy = true;

        $connectionsConfig = config('database.redis');

        unset($connectionsConfig['client']);
        unset($connectionsConfig['options']);
        unset($connectionsConfig['clusters']); // yup, no clustering support atm

        foreach ($connectionsConfig as $connection => $config) {
            if (isset($config['skip-health-check']) && $config['skip-health-check']) {
                continue;
            }

            $isOptional = (isset($config['optional-health-check']) && $config['optional-health-check']) && !$isStrict;

            $checks[$connection] = $this->checkConnection($connection);
            $healthy = $healthy && ($checks[$connection] || $isOptional);

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
