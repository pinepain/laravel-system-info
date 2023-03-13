<?php declare(strict_types=1);

namespace Pinepain\SystemInfo\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Routing\Controller;


class EchoController extends Controller
{
    public function __invoke(Request $request)
    {
        $response = response($content = $request->getContent());

        if ($reqContentType = $request->headers->get('Content-Type')) {
            $response->header('Content-Type', $reqContentType);
        }

        if ('' === $content) {
            return response()->noContent();
        }

        return $response;
    }
}
