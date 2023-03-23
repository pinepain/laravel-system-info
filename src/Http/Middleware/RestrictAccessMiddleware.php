<?php declare(strict_types=1);

namespace Pinepain\SystemInfo\Http\Middleware;


use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\IpUtils;


class RestrictAccessMiddleware
{
    private int $minTokenLength = 16;
    private array $privateLocations = [
        '127.0.0.0/8',
        '10.0.0.0/8',
        '172.16.0.0/12',
        '192.168.0.0/16',
    ];

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
        if (!($routeName = $request->route()?->getName())) {
            abort(Response::HTTP_UNAUTHORIZED);
        }

        $isPublicRoute = config("system-info.http.public-routes", [])[$routeName] ?? false;
        $locationIsPrivate = $this->isPrivate($request);
        $requestIsAuthorized = $this->hasValidToken($request);

        $request->setUserResolver(fn () => $locationIsPrivate || $requestIsAuthorized);

        if ($locationIsPrivate || $requestIsAuthorized || $isPublicRoute) {
            return $next($request);
        }

        abort(Response::HTTP_UNAUTHORIZED);
    }

    private function isPrivate(Request $request): bool
    {
        $allowedIps = explode(',', config('system-info.http.allowed-ips'));
        $allowedIps = array_filter(array_map(fn($v) => trim($v), $allowedIps));

        if (false !== ($pos = array_search('private', $allowedIps))) {
            unset($allowedIps[$pos]);
            $allowedIps = array_merge($this->privateLocations, $allowedIps);
        }

        $allowedIps = array_unique($allowedIps);

        return IpUtils::checkIp($request->ip(), $allowedIps);
    }

    private function hasValidToken(Request $request): bool
    {
        $allowedTokens = explode(',', config('system-info.http.allowed-tokens'));
        $allowedTokens = array_filter(array_map(fn($v) => trim($v), $allowedTokens));

        $token = $request->query->get('t', $request->query->get('token', ''));
        if (!$token || !is_string($token) || strlen($token) < $this->minTokenLength) {
            return false;
        }

        return in_array($token, $allowedTokens);
    }
}
