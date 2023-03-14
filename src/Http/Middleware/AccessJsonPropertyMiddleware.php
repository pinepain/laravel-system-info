<?php declare(strict_types=1);

namespace Pinepain\SystemInfo\Http\Middleware;


use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use stdClass;


class AccessJsonPropertyMiddleware
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
        $response = $next($request);

        if ($response instanceof JsonResponse && ($component = $request->route('component')) && is_array($values = $response->getData(true))) {
            $noValue = new stdClass();

            $value = array_reduce(
                explode('/', $component),
                fn($carry, $v) => $carry && is_array($carry) && array_key_exists($v, $carry) ? $carry[$v] : $noValue,
                $values
            );

            if ($noValue === $value) {
                return response('', Response::HTTP_NOT_FOUND);
            }

            $values = $value;
            if (is_scalar($values) && $this->wantsScalar($request)) {
                if (is_bool($value)) {
                    $value = json_encode($value); // we treat boolean scalar same as they are in json
                }

                return response((string)$value, $response->status(), ['Content-Type' => 'text/plain']);
            } else {
                return $response->setData($values);
            }
        }

        return $response;
    }

    private function wantsScalar(Request $request): bool
    {
        return $request->query->has('s') || $request->query->has('scalar');
    }
}
