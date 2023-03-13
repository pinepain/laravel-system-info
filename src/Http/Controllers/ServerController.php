<?php declare(strict_types=1);

namespace Pinepain\SystemInfo\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Routing\Controller;


class ServerController extends Controller
{
    public function __invoke(Request $request)
    {
        $values = [];

        $values['web-server'] = $request->server('SERVER_SOFTWARE');
        $values['php-version'] = phpversion();
        $values['laravel-version'] = app()->version();
        $values['has-php-ini-admin-value'] = $request->server->has('PHP_ADMIN_VALUE');

        return response()->json($values);
    }
}
