<?php declare(strict_types=1);

namespace Pinepain\SystemInfo\Checkers;


use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Throwable;


class SessionStatusChecker implements CheckerInterface
{
    public function getName(): string
    {
        return 'session';
    }

    public function check(mixed ...$args): Result
    {
        try {
            /** @var \Illuminate\Session\Store $driver */
            $driver = Session::driver();

            $driver->getHandler()->read('pinepain/laravel-system-info.check.session.' . time());

            return new Result(true);
        } catch (Throwable $e) {
            Log::warning("Session status check failed", ['e' => $e->getMessage(), 'class' => get_class($e), 'trace' => $e->getTraceAsString()]);
        }

        return new Result(false);
    }
}
