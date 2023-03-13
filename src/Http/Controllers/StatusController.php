<?php declare(strict_types=1);

namespace Pinepain\SystemInfo\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Pinepain\SystemInfo\Checkers\StatusChecker;


class StatusController extends Controller
{
    public function __invoke(Request $request, StatusChecker $checker)
    {
        $failFast = !$this->shouldDoFullCheck($request);
        $components = $this->getComponentsToCheck($request);

        $status = $checker->check($failFast, ...$components);

        $healthy = $status->isHealthy();

        $values = [
            'healthy' => $healthy,
            'label' => $healthy ? 'OK' : 'FAIL'
        ];

        if ($this->shouldShowDetails($request)) {
            $values['details'] = $status->getDetails();
        }

        return response()->json($values, $healthy ? 200 : 500);
    }

    private function getComponentsToCheck(Request $request): array
    {
        $customChecksArePrivate = config('system-info.http.custom-checks-are-private');
        $wantsToCheck = $request->query->get('c', $request->query->get('check', ''));

        if ($customChecksArePrivate && !$request->user()) {
            return [];
        }

        $checks = [];
        if ($wantsToCheck) {
            if (is_string($wantsToCheck)) {
                $wantsToCheck = explode(',', $wantsToCheck);
            }

            $wantsToCheck = array_map(fn($v) => is_string($v) ? trim($v) : '', $wantsToCheck);
            $checks = array_filter($wantsToCheck);
        }

        return $checks;
    }

    private function shouldShowDetails(Request $request): bool
    {
        $detailsArePrivate = config('system-info.http.details-are-private');
        $wantsDetails = $request->query->has('d') || $request->query->has('details');

        if ($detailsArePrivate && !$request->user()) {
            return false;
        }

        return $wantsDetails;
    }

    private function shouldDoFullCheck(Request $request):bool
    {
        $fullCheckIsPrivate = config('system-info.http.full-check-is-private');
        $wantsFullCheck = $request->query->has('f') || $request->query->has('full');

        if ($fullCheckIsPrivate && !$request->user()) {
            return false;
        }

        return $wantsFullCheck;
    }
}
