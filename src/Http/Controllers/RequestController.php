<?php declare(strict_types=1);

namespace Pinepain\SystemInfo\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Routing\Controller;


class RequestController extends Controller
{
    public function __invoke(Request $request)
    {
        $values = [];

        $values['protocol'] = $request->getProtocolVersion();
        $values['method'] = $request->getMethod();
        $values['real-method'] = $request->getRealMethod();
        $values['scheme'] = $request->getScheme();
        $values['host'] = $request->getHost();
        $values['port'] = $request->getPort();
        $values['query'] = $request->query();

        $values['ip'] = $request->ip();
        if (count($request->ips()) > 1) {
            $values['ips'] = $request->ips();
        }

        $values['user-agent'] = $request->userAgent();
        $headers = $request->headers->all();
        $headers = array_map(fn($v) => count($v) == 1 ? $v[0] : $v, $headers);
        $values['headers'] = $headers;

        $values['is-secure'] = $request->isSecure();
        $values['is-no-cache'] = $request->isNoCache();
        $values['is-xml-http-request'] = $request->isXmlHttpRequest();
        $values['is-json'] = $request->isJson();
        $values['wants-json'] = $request->wantsJson();
        $values['is-from-trusted-proxy'] = $request->isFromTrustedProxy();
        $values['is-method-cacheable'] = $request->isMethodCacheable();
        $values['is-method-safe'] = $request->isMethodSafe();
        $values['is-method-idempotent'] = $request->isMethodIdempotent();
        $values['is-pjax'] = $request->pjax();

        $values['content'] = $request->getContent();

        return response()->json($values);
    }
}
