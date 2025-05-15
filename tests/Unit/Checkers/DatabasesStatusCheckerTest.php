<?php declare(strict_types=1);

namespace Pinepain\SystemInfo\Tests\Unit\Checkers;


use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Pinepain\SystemInfo\Checkers\CheckerInterface;
use Pinepain\SystemInfo\Checkers\DatabasesStatusChecker;
use RuntimeException;


class DatabasesStatusCheckerTest extends TestCase
{
    public function testInstantiableAndImplementsInterface()
    {
        $checker = new DatabasesStatusChecker();

        $this->assertInstanceOf(CheckerInterface::class, $checker);
        $this->assertSame('db', $checker->getName());
    }

    public function testCheckingFailingSingleConnection()
    {
        config()->set('database.connections', ['test' => []]);

        DB::shouldReceive('connection')
            ->once()
            ->withArgs(['test'])
            ->andThrow(new RuntimeException('Test exception'));

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $args) {
                if ("Database connection 'test' status check failed" != $message) {
                    return false;
                }
                if ('test' != $args['connection']) {
                    return false;
                }
                if ('Test exception' != $args['e']) {
                    return false;
                }
                if ('RuntimeException' != $args['class']) {
                    return false;
                }

                if (!str_starts_with($args['trace'], '#0')) {
                    return false;
                }

                return true;
            });

        $checker = new DatabasesStatusChecker();
        $result = $checker->check();

        $this->assertFalse($result->isHealthy());
        $this->assertSame(['test' => false], $result->getDetails());
    }

    public function testCheckingFailingMultipleConnectionsWithFailFastOn()
    {
        config()->set('database.connections', ['first' => [], 'second' => []]);

        DB::shouldReceive('connection')
            ->once()
            ->withArgs(['first'])
            ->andThrow(new RuntimeException('Test exception - first'));

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $args) {
                if ("Database connection 'first' status check failed" != $message) {
                    return false;
                }
                if ('first' != $args['connection']) {
                    return false;
                }
                if ('Test exception - first' != $args['e']) {
                    return false;
                }
                if ('RuntimeException' != $args['class']) {
                    return false;
                }

                if (!str_starts_with($args['trace'], '#0')) {
                    return false;
                }

                return true;
            });

        $checker = new DatabasesStatusChecker();
        $result = $checker->check();

        $this->assertFalse($result->isHealthy());
        $this->assertSame(['first' => false], $result->getDetails());
    }

    public function testCheckingWithSkip()
    {
        config()->set('database.connections', ['first' => ['skip-health-check' => true], 'second' => []]);

        $second = $this->mock(Connection::class);
        $second->expects('getPdo')
            ->once()
            ->withNoArgs()
            ->andReturnTrue();

        DB::shouldReceive('connection')
            ->once()
            ->withArgs(['second'])
            ->andReturn($second);

        $checker = new DatabasesStatusChecker();
        $result = $checker->check();

        $this->assertTrue($result->isHealthy());
        $this->assertSame(['second' => true], $result->getDetails());
    }

    public function testCheckingFailingMultipleConnectionsWithoutFailFast()
    {
        config()->set('database.connections', ['first' => [], 'second' => []]);

        DB::shouldReceive('connection')
            ->once()
            ->withArgs(['first'])
            ->andThrow(new RuntimeException('Test exception - first'));

        DB::shouldReceive('connection')
            ->once()
            ->withArgs(['second'])
            ->andThrow(new RuntimeException('Test exception - second'));

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $args) {
                if ("Database connection 'first' status check failed" != $message) {
                    return false;
                }
                if ('first' != $args['connection']) {
                    return false;
                }
                if ('Test exception - first' != $args['e']) {
                    return false;
                }
                if ('RuntimeException' != $args['class']) {
                    return false;
                }

                if (!str_starts_with($args['trace'], '#0')) {
                    return false;
                }

                return true;
            });

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $args) {
                if ("Database connection 'second' status check failed" != $message) {
                    return false;
                }
                if ('second' != $args['connection']) {
                    return false;
                }
                if ('Test exception - second' != $args['e']) {
                    return false;
                }
                if ('RuntimeException' != $args['class']) {
                    return false;
                }

                if (!str_starts_with($args['trace'], '#0')) {
                    return false;
                }

                return true;
            });

        $checker = new DatabasesStatusChecker();
        $result = $checker->check(failFast: false);

        $this->assertFalse($result->isHealthy());
        $this->assertSame(['first' => false, 'second' => false], $result->getDetails());
    }

    #[DataProvider('isStrictProvider')]
    public function testCheckingFailingOptionalConnection($isStrict)
    {
        config()->set('database.connections', ['first' => ['optional-health-check' => true], 'second' => []]);

        DB::shouldReceive('connection')
            ->once()
            ->withArgs(['first'])
            ->andThrow(new RuntimeException('Test exception - first'));

        $second = $this->mock(Connection::class);
        $second->expects('getPdo')
            ->once()
            ->withNoArgs()
            ->andReturnTrue();

        DB::shouldReceive('connection')
            ->once()
            ->withArgs(['second'])
            ->andReturn($second);

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $args) {
                if ("Database connection 'first' status check failed" != $message) {
                    return false;
                }
                if ('first' != $args['connection']) {
                    return false;
                }
                if ('Test exception - first' != $args['e']) {
                    return false;
                }
                if ('RuntimeException' != $args['class']) {
                    return false;
                }

                if (!str_starts_with($args['trace'], '#0')) {
                    return false;
                }

                return true;
            });

        $checker = new DatabasesStatusChecker();
        $result = match ($isStrict) {
            null => $checker->check(failFast: false),
            default => $checker->check(failFast: false, strict: $isStrict),
        };

        $this->assertSame(!$isStrict, $result->isHealthy());
        $this->assertSame(['first' => false, 'second' => true], $result->getDetails());
    }

    public function testCheckingPassingSingleConnection()
    {
        config()->set('database.connections', ['first' => []]);

        $connection = $this->mock(Connection::class);

        $connection->expects('getPdo')
            ->once()
            ->withNoArgs()
            ->andReturnNull();

        DB::shouldReceive('connection')
            ->once()
            ->withArgs(['first'])
            ->andReturn($connection);

        $checker = new DatabasesStatusChecker();
        $result = $checker->check();

        $this->assertTrue($result->isHealthy());
        $this->assertSame(['first' => true], $result->getDetails());
    }

    public function testCheckingFailOneThenPassAnother()
    {
        config()->set('database.connections', ['first' => [], 'second' => []]);

        $first = $this->mock(Connection::class);
        $first->expects('getPdo')
            ->once()
            ->withNoArgs()
            ->andThrow(new RuntimeException('Failing first ping'));

        $second = $this->mock(Connection::class);
        $second->expects('getPdo')
            ->once()
            ->withNoArgs()
            ->andReturnTrue();

        DB::shouldReceive('connection')
            ->once()
            ->withArgs(['first'])
            ->andReturn($first);

        DB::shouldReceive('connection')
            ->once()
            ->withArgs(['second'])
            ->andReturn($second);

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $args) {
                if ("Database connection 'first' status check failed" != $message) {
                    return false;
                }
                if ('first' != $args['connection']) {
                    return false;
                }
                if ('Failing first ping' != $args['e']) {
                    return false;
                }
                if ('RuntimeException' != $args['class']) {
                    return false;
                }

                if (!str_starts_with($args['trace'], '#0')) {
                    return false;
                }

                return true;
            });

        $checker = new DatabasesStatusChecker();
        $result = $checker->check(failFast: false);

        $this->assertFalse($result->isHealthy());
        $this->assertSame(['first' => false, 'second' => true], $result->getDetails());
    }

    // read-write support

    public function testSplitConnectionWithBadConfig()
    {
        config()->set('database.connections', ['test' => ['read' => []]]);

        DB::shouldReceive('connection')
            ->once()
            ->withArgs(['test'])
            ->andThrow(new RuntimeException('Test exception'));

        DB::shouldReceive('connection')
            ->never()
            ->withArgs(['test::read']);

        $checker = new DatabasesStatusChecker();
        $result = $checker->check();

        $this->assertFalse($result->isHealthy());
        $this->assertSame(['test' => false], $result->getDetails());

        config()->set('database.connections', ['test' => ['write' => []]]);

        DB::shouldReceive('connection')
            ->once()
            ->withArgs(['test'])
            ->andThrow(new RuntimeException('Test exception'));

        DB::shouldReceive('connection')
            ->never()
            ->withArgs(['test::write']);

        $checker = new DatabasesStatusChecker();
        $result = $checker->check();

        $this->assertFalse($result->isHealthy());
        $this->assertSame(['test' => false], $result->getDetails());
    }

    public function testCheckingFailingSingleSplitConnectionOnRead()
    {
        config()->set('database.connections', ['test' => ['read' => [], 'write' => []], 'second' => []]);

        DB::shouldReceive('connection')
            ->once()
            ->withArgs(['test::read'])
            ->andThrow(new RuntimeException('Test exception'));

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $args) {
                if ("Database connection 'test::read' status check failed" != $message) {
                    return false;
                }
                if ('test::read' != $args['connection']) {
                    return false;
                }
                if ('Test exception' != $args['e']) {
                    return false;
                }
                if ('RuntimeException' != $args['class']) {
                    return false;
                }

                if (!str_starts_with($args['trace'], '#0')) {
                    return false;
                }

                return true;
            });

        $checker = new DatabasesStatusChecker();
        $result = $checker->check();

        $this->assertFalse($result->isHealthy());
        $this->assertSame(['test::read' => false], $result->getDetails());
    }

    public function testCheckingFailingSingleSplitConnectionOnWrite()
    {
        config()->set('database.connections', ['test' => ['read' => [], 'write' => []], 'second' => []]);

        $connection = $this->mock(Connection::class);

        $connection->expects('getPdo')
            ->once()
            ->withNoArgs()
            ->andReturnNull();

        DB::shouldReceive('connection')
            ->once()
            ->withArgs(['test::read'])
            ->andReturn($connection);

        DB::shouldReceive('connection')
            ->once()
            ->withArgs(['test::write'])
            ->andThrow(new RuntimeException('Test exception'));

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $args) {
                if ("Database connection 'test::write' status check failed" != $message) {
                    return false;
                }
                if ('test::write' != $args['connection']) {
                    return false;
                }
                if ('Test exception' != $args['e']) {
                    return false;
                }
                if ('RuntimeException' != $args['class']) {
                    return false;
                }

                if (!str_starts_with($args['trace'], '#0')) {
                    return false;
                }

                return true;
            });

        $checker = new DatabasesStatusChecker();
        $result = $checker->check();

        $this->assertFalse($result->isHealthy());
        $this->assertSame(['test::read' => true, 'test::write' => false], $result->getDetails());
    }

    public function testCheckingFailingSingleSplitConnectionOnReadWithoutFailFast()
    {
        config()->set('database.connections', ['test' => ['read' => [], 'write' => []], 'second' => []]);

        $connection = $this->mock(Connection::class);

        $connection->expects('getPdo')
            ->times(2)
            ->withNoArgs()
            ->andReturnNull();

        DB::shouldReceive('connection')
            ->once()
            ->withArgs(['test::read'])
            ->andThrow(new RuntimeException('Test exception'));

        DB::shouldReceive('connection')
            ->once()
            ->withArgs(['test::write'])
            ->andReturn($connection);

        DB::shouldReceive('connection')
            ->once()
            ->withArgs(['second'])
            ->andReturn($connection);

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $args) {
                if ("Database connection 'test::read' status check failed" != $message) {
                    return false;
                }
                if ('test::read' != $args['connection']) {
                    return false;
                }
                if ('Test exception' != $args['e']) {
                    return false;
                }
                if ('RuntimeException' != $args['class']) {
                    return false;
                }

                if (!str_starts_with($args['trace'], '#0')) {
                    return false;
                }

                return true;
            });

        $checker = new DatabasesStatusChecker();
        $result = $checker->check(failFast: false);

        $this->assertFalse($result->isHealthy());
        $this->assertSame(['test::read' => false, 'test::write' => true, 'second' => true], $result->getDetails());
    }

    public static function isStrictProvider(): array
    {
        return [
            [null],
            [true],
            [false],
        ];
    }
}
