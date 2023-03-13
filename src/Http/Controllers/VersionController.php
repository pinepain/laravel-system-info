<?php declare(strict_types=1);

namespace Pinepain\SystemInfo\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Pinepain\SystemInfo\Checkers\VersionChecker;


class VersionController extends Controller
{
    public function __invoke(Request $request, VersionChecker $checker)
    {
        $result = $checker->check();

        if (!$result->isHealthy()) {
            abort(404);
        }

        return response()->json($result->getDetails());
    }
}
