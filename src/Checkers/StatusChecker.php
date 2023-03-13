<?php declare(strict_types=1);

namespace Pinepain\SystemInfo\Checkers;


class StatusChecker
{
    /**
     * @var \Pinepain\SystemInfo\Checkers\CheckerInterface[]
     */
    private array $checkers;

    public function __construct(CheckerInterface ...$checkers)
    {
        $this->checkers = array_combine(array_map(fn($x) => $x->getName(), $checkers), $checkers);
    }

    public function getName(): string
    {
        return 'status';
    }

    public function check(bool $failFast = true, string ...$components): Result
    {
        $checks = [];
        $healthy = true;

        $checkers = $this->checkers;
        if ($components) {
            $checkers = array_filter($checkers, fn($x) => in_array($x, $components), ARRAY_FILTER_USE_KEY);
        }

        foreach ($checkers as $name => $checker) {
            $result = $checker->check($failFast);
            $checks[$name] = $result;
            $healthy = $healthy && $result->isHealthy();

            if (!$healthy && $failFast) {
                break;
            }
        }

        return new Result($healthy, $checks);
    }
}
