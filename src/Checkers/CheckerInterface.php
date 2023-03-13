<?php declare(strict_types=1);

namespace Pinepain\SystemInfo\Checkers;


interface CheckerInterface
{
    public function getName(): string;
    public function check(bool $failFast = true): Result;
}
