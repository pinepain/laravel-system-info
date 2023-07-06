<?php declare(strict_types=1);

namespace Pinepain\SystemInfo\Tests\Unit\Checkers;


use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Orchestra\Testbench\TestCase;
use Pinepain\SystemInfo\Checkers\CheckerInterface;
use Pinepain\SystemInfo\Checkers\RedisStatusChecker;
use RuntimeException;


class RedisStatusCheckerTest extends TestCase
{
    public function testInstantiableAndImplementsInterface()
    {
        $checker = new RedisStatusChecker();

        $this->assertInstanceOf(CheckerInterface::class, $checker);
        $this->assertSame('redis', $checker->getName());
    }

    public function testCheckingFailingSingleConnection()
    {
        config()->set('database.redis', ['test' => []]);

        Redis::shouldReceive('connection')
            ->once()
            ->withArgs(['test'])
            ->andThrow(new RuntimeException('Test exception'));

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $args) {
                if ("Redis connection 'test' status check failed" != $message) {
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

        $checker = new RedisStatusChecker();
        $result = $checker->check();

        $this->assertFalse($result->isHealthy());
        $this->assertSame(['test' => false], $result->getDetails());
    }

    public function testCheckingFailingMultipleConnectionsWithFailFastOn()
    {
        config()->set('database.redis', ['first' => [], 'second' => []]);

        Redis::shouldReceive('connection')
            ->once()
            ->withArgs(['first'])
            ->andThrow(new RuntimeException('Test exception - first'));

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $args) {
                if ("Redis connection 'first' status check failed" != $message) {
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

        $checker = new RedisStatusChecker();
        $result = $checker->check();

        $this->assertFalse($result->isHealthy());
        $this->assertSame(['first' => false], $result->getDetails());
    }

    public function testCheckingFailingMultipleConnectionsWithoutFailFast()
    {
        config()->set('database.redis', ['first' => [], 'second' => []]);

        Redis::shouldReceive('connection')
            ->once()
            ->withArgs(['first'])
            ->andThrow(new RuntimeException('Test exception - first'));

        Redis::shouldReceive('connection')
            ->once()
            ->withArgs(['second'])
            ->andThrow(new RuntimeException('Test exception - second'));

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $args) {
                if ("Redis connection 'first' status check failed" != $message) {
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
                if ("Redis connection 'second' status check failed" != $message) {
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

        $checker = new RedisStatusChecker();
        $result = $checker->check(failFast: false);

        $this->assertFalse($result->isHealthy());
        $this->assertSame(['first' => false, 'second' => false], $result->getDetails());
    }

    public function testCheckingPassingSingleConnection()
    {
        config()->set('database.redis', ['first' => []]);

        $connection = $this->mock(Connection::class);

        $connection->expects('command')
            ->once()
            ->withArgs(['ping'])
            ->andReturnTrue();

        Redis::shouldReceive('connection')
            ->once()
            ->withArgs(['first'])
            ->andReturn($connection);

        $checker = new RedisStatusChecker();
        $result = $checker->check();

        $this->assertTrue($result->isHealthy());
        $this->assertSame(['first' => true], $result->getDetails());
    }

    public function testCheckingFailOneThenPassAnother()
    {
        config()->set('database.redis', ['first' => [], 'second' => []]);

        $first = $this->mock(Connection::class);
        $first->expects('command')
            ->once()
            ->withArgs(['ping'])
            ->andThrow(new RuntimeException('Failing first ping'));

        $second = $this->mock(Connection::class);
        $second->expects('command')
            ->once()
            ->withArgs(['ping'])
            ->andReturnTrue();

        Redis::shouldReceive('connection')
            ->once()
            ->withArgs(['first'])
            ->andReturn($first);

        Redis::shouldReceive('connection')
            ->once()
            ->withArgs(['second'])
            ->andReturn($second);

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $args) {
                if ("Redis connection 'first' status check failed" != $message) {
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

        $checker = new RedisStatusChecker();
        $result = $checker->check(failFast: false);

        $this->assertFalse($result->isHealthy());
        $this->assertSame(['first' => false, 'second' => true], $result->getDetails());
    }

    public function testCheckingWithSkip()
    {
        config()->set('database.redis', ['first' => ['skip-health-check' => true], 'second' => []]);

        $second = $this->mock(Connection::class);
        $second->expects('command')
            ->once()
            ->withArgs(['ping'])
            ->andReturnTrue();

        Redis::shouldReceive('connection')
            ->once()
            ->withArgs(['second'])
            ->andReturn($second);

        $checker = new RedisStatusChecker();
        $result = $checker->check();

        $this->assertTrue($result->isHealthy());
        $this->assertSame(['second' => true], $result->getDetails());
    }

}
