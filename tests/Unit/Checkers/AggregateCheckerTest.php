<?php declare(strict_types=1);

namespace Pinepain\SystemInfo\Tests\Unit\Checkers;


use Orchestra\Testbench\TestCase;
use Pinepain\SystemInfo\Checkers\AggregateStatusChecker;
use Pinepain\SystemInfo\Checkers\CheckerInterface;
use Pinepain\SystemInfo\Checkers\Result;


class AggregateCheckerTest extends TestCase
{
    public function testInstantiableAndImplementsInterface()
    {
        $checker = new AggregateStatusChecker();

        $this->assertInstanceOf(CheckerInterface::class, $checker);
        $this->assertSame('aggregate', $checker->getName());
    }

    public function testCheckAllHealthy()
    {
        $first = $this->getChecker('first', true, $firstResult = new Result(true));
        $second = $this->getChecker('second', true, $secondResult = new Result(true));

        $checker = new AggregateStatusChecker($first, $second);
        $result = $checker->check();

        $this->assertTrue($result->isHealthy());
        $this->assertSame(['first' => $firstResult, 'second' => $secondResult], $result->getDetails());
    }

    public function testCheckFirstFails()
    {
        $first = $this->getChecker('first', true, $firstResult = new Result(false));
        $second = $this->getChecker('second', true, $secondResult = new Result(true));

        $checker = new AggregateStatusChecker($first, $second);
        $result = $checker->check();

        $this->assertFalse($result->isHealthy());
        $this->assertSame(['first' => $firstResult], $result->getDetails());
    }

    public function testCheckSecondFails()
    {
        $first = $this->getChecker('first', true, $firstResult = new Result(true));
        $second = $this->getChecker('second', true, $secondResult = new Result(false));

        $checker = new AggregateStatusChecker($first, $second);
        $result = $checker->check();

        $this->assertFalse($result->isHealthy());
        $this->assertSame(['first' => $firstResult, 'second' => $secondResult], $result->getDetails());
    }

    public function testCheckFirstFailsWithoutFailFast()
    {
        $first = $this->getChecker('first', false, $firstResult = new Result(true));
        $second = $this->getChecker('second', false, $secondResult = new Result(false));

        $checker = new AggregateStatusChecker($first, $second);
        $result = $checker->check(failFast: false);

        $this->assertFalse($result->isHealthy());
        $this->assertSame(['first' => $firstResult, 'second' => $secondResult], $result->getDetails());
    }

    public function testCheckStrictIsPassedDown()
    {
        $first = $this->getChecker('first', true, $firstResult = new Result(true), true);
        $second = $this->getChecker('second', true, $secondResult = new Result(true), true);

        $checker = new AggregateStatusChecker($first, $second);
        $result = $checker->check(strict: true);

        $this->assertTrue($result->isHealthy());
        $this->assertSame(['first' => $firstResult, 'second' => $secondResult], $result->getDetails());
    }

    public function testCheckSpecificComponents()
    {
        $first = $this->getChecker('first', true, $firstResult = new Result(false));
        $second = $this->getChecker('second', true, $secondResult = new Result(true));

        $checker = new AggregateStatusChecker($first, $second);
        $result = $checker->check(components: ['second']);

        $this->assertTrue($result->isHealthy());
        $this->assertSame(['second' => $secondResult], $result->getDetails());
    }

    private function getChecker(string $name, bool $expectedToFailFast, Result $result, bool $expectedIsStrict = false): CheckerInterface
    {
        $tests = [
            fn(...$args) => $this->assertSame($expectedToFailFast, ($args['failFast'] ?? false)),
            fn(...$args) => $this->assertSame($expectedIsStrict, ($args['strict'] ?? false)),
        ];

        return new class($name, $result, $tests) implements CheckerInterface {
            public function __construct(private string $name, private Result $result, private array $tests) {}

            public function getName(): string
            {
                return $this->name;
            }

            public function check(...$args): Result
            {
                foreach ($this->tests as $t) {
                    call_user_func($t, ...$args);
                }

                return $this->result;
            }
        };
    }
}
