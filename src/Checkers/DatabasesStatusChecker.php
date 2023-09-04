<?php declare(strict_types=1);

namespace Pinepain\SystemInfo\Checkers;


use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;


class DatabasesStatusChecker implements CheckerInterface
{
    public function getName(): string
    {
        return 'db';
    }

    public function check(mixed ...$args): Result
    {
        $failFast = $args['failFast'] ?? true;
        $isStrict = $args['strict'] ?? false;

        $checks = [];
        $healthy = true;

        $connectionsConfig = config('database.connections');

        foreach ($connectionsConfig as $connection => $config) {
            if (isset($config['skip-health-check']) && $config['skip-health-check']) {
                continue;
            }

            $isOptional = (isset($config['optional-health-check']) && $config['optional-health-check']) && !$isStrict;

            if (array_key_exists('read', $config) && array_key_exists('write', $config)) {
                foreach (["{$connection}::read", "{$connection}::write"] as $c) {
                    $checks[$c] = $this->checkConnection($c);
                    $healthy = $healthy && ($checks[$c] || $isOptional);

                    if (!$healthy && $failFast) {
                        break 2;
                    }
                }
            } else {
                $checks[$connection] = $this->checkConnection($connection);
                $healthy = $healthy && ($checks[$connection] || $isOptional);

                if (!$healthy && $failFast) {
                    break;
                }
            }
        }

        return new Result($healthy, $checks);
    }

    private function checkConnection(string $connection): bool
    {
        try {
            DB::connection($connection)->getPdo();

            return true;
        } catch (Throwable $e) {
            Log::warning("Database connection '{$connection}' status check failed", ['connection' => $connection, 'e' => $e->getMessage(), 'class' => get_class($e), 'trace' => $e->getTraceAsString()]);
        }

        return false;
    }
}
