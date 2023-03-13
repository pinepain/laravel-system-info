<?php declare(strict_types=1);

namespace Pinepain\SystemInfo\Http\Controllers;


use Illuminate\Routing\Controller;


class PingController extends Controller
{
    public function __invoke()
    {
        return response('pong')
            ->header('Content-Type', 'text/plain');
    }
}
