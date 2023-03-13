<?php declare(strict_types=1);

namespace Pinepain\SystemInfo\Checkers;


use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Throwable;


class SessionStatusChecker implements CheckerInterface
{
    public function getName(): string
    {
        return 'session';
    }

    public function check(bool $failFast = true): Result
    {
        /** @var \Illuminate\Session\Store $driver */
        $driver = Session::driver();

        try {
            $driver->getHandler()->read('system-info-check-session-store-' . rand());

            return new Result(true);
        } catch (Throwable $e) {
            Log::error("Session status check failed", ['e' => $e->getMessage(), 'class' => get_class($e), 'trace' => $e->getTraceAsString()]);
        }

        return new Result(false);
    }
}
