<?php declare(strict_types=1);

namespace Pinepain\SystemInfo\Checkers;


use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;


class CacheStatusChecker implements CheckerInterface
{
    public function getName(): string
    {
        return 'cache';
    }

    public function check(mixed ...$args): Result
    {
        $failFast = $args['failFast'] ?? true;

        $checks = [];
        $healthy = true;

        foreach (config('cache.stores') as $store => $config) {
            if (isset($config['skip-health-check']) && $config['skip-health-check']) {
                continue;
            }

            $checks[$store] = $this->checkStore($store);
            $healthy = $healthy && $checks[$store];

            if (!$healthy && $failFast) {
                break;
            }
        }

        return new Result($healthy, $checks);
    }

    private function checkStore(string $store): bool
    {
        try {
            Cache::store($store)->get('pinepain/laravel-system-info.check.cache.' . time());

            return true;
        } catch (Throwable $e) {
            Log::warning("Cache store '{$store}' status check failed", ['store' => $store, 'e' => $e->getMessage(), 'class' => get_class($e), 'trace' => $e->getTraceAsString()]);
        }

        return false;
    }
}
