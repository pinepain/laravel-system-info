<?php declare(strict_types=1);

namespace Pinepain\SystemInfo\Http\Middleware;


use Closure;
use Illuminate\Http\Response;


class CacheHeadersMiddleware
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

        try {
            return $response = $next($request);
        } finally {
            if (isset($response) && $response instanceof Response) {
                $response->setCache([
                    'no_cache' => true,
                    'no_store' => true,
                    'private' => true,
                ]);
            }
        }
    }
}
