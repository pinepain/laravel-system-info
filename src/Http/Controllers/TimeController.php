<?php declare(strict_types=1);

namespace Pinepain\SystemInfo\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Routing\Controller;


class TimeController extends Controller
{
    public function __invoke(Request $request)
    {
        $values = [
            'request' => $req = $request->server->get('REQUEST_TIME_FLOAT'),
            'now' => $now = microtime(true),
            'diff' => round($now - $req, 6),
        ];

        $values['utc'] = now()->timezone('utc')->format('D, d M Y H:i:s T');
        $values['local'] = now()->format('D, d M Y H:i:s T');

        return response()
            ->json($values);
    }
}
