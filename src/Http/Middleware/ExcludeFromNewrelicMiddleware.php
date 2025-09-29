<?php declare(strict_types=1);

namespace Pinepain\SystemInfo\Http\Middleware;


use Closure;


class ExcludeFromNewrelicMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Closure  $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (extension_loaded('newrelic') && !config('system-info.newrelic.web', true)) {
            newrelic_ignore_transaction();
        }

        return $next($request);
    }
}
