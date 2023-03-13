<?php declare(strict_types=1);

namespace Pinepain\SystemInfo\Http\Middleware;


use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Pinepain\SystemInfo\Checkers\VersionChecker;
use Symfony\Component\HttpFoundation\IpUtils;


class SetVersionHeadersMiddleware
{
    private VersionChecker $version;

    public function __construct(VersionChecker $version)
    {
        $this->version = $version;
    }

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
            if (isset($response) && $response instanceof Response && $this->shallAddVersion($request)) {
                $versionDetails = $this->version->check()->getDetails();

                foreach ($versionDetails as $k => $v) {
                    $header = 'X-Version-' . ucwords($k, '-');
                    $response->header($header, $v);
                }
            }
        }
    }

    private function shallAddVersion(Request $request): bool
    {
        if (!config('system-info.http.version-is-private')) {
            return true;
        }

        return IpUtils::checkIp($request->ip(), (array)config('system-info.http.allowed-ips', []));
    }
}
