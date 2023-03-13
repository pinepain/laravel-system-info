<?php declare(strict_types=1);

namespace Pinepain\SystemInfo\Http\Middleware;


use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


class MaybePrettyJsonMiddleware
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
            if (isset($response) && ($response instanceof JsonResponse)) {
                $response->setEncodingOptions($this->wantsPretty($request) ? JSON_PRETTY_PRINT : 0);
                $response->setJson($response->getContent() . PHP_EOL);
            }
        }
    }

    private function wantsPretty(Request $request): bool
    {
        return $request->query->has('p') || $request->query->has('pretty');
    }
}
