<?php declare(strict_types=1);

namespace Pinepain\SystemInfo\Checkers;


class VersionChecker implements CheckerInterface
{
    public function getName(): string
    {
        return 'version';
    }

    public function check(bool $failFast = true): Result
    {
        $values = array_filter([
            'name' => config('app.name'),
            'env' => config('app.env'),
            'hash' => env('COMMIT_HASH'),
            'deployed-at' => env('DEPLOYED_AT'),
            'built-at' => env('BUILT_AT'),
            'host' => gethostname(),
        ]);

        return new Result(!empty($values), $values);
    }
}
