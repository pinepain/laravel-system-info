<?php declare(strict_types=1);

namespace Pinepain\SystemInfo\Checkers;


class VersionChecker implements CheckerInterface
{
    public function getName(): string
    {
        return 'version';
    }

    public function check(mixed ...$args): Result
    {
        $values = array_filter(
            array_merge(
                [
                    'name' => config('app.name'),
                    'env' => config('app.env'),
                ],
                config('system-info.version')
            )
        );

        return new Result(!empty($values), $values);
    }
}

