<?php declare(strict_types=1);

namespace Pinepain\SystemInfo\Tests\Unit\Checkers;


use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Orchestra\Testbench\TestCase;
use Pinepain\SystemInfo\Checkers\CacheStatusChecker;
use Pinepain\SystemInfo\Checkers\CheckerInterface;
use Psr\SimpleCache\CacheInterface;
use RuntimeException;


class CacheStatusCheckerTest extends TestCase
{
    public function testInstantiableAndImplementsInterface()
    {
        $checker = new CacheStatusChecker();

        $this->assertInstanceOf(CheckerInterface::class, $checker);
        $this->assertSame('cache', $checker->getName());
    }

    public function testCheckingFailingSingleConnection()
    {
        config()->set('cache.stores', ['test' => []]);

        Cache::shouldReceive('store')
            ->once()
            ->withArgs(['test'])
            ->andThrow(new RuntimeException('Test exception'));

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $args) {
                if ("Cache store 'test' status check failed" != $message) {
                    return false;
                }
                if ('test' != $args['store']) {
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

        $checker = new CacheStatusChecker();
        $result = $checker->check();

        $this->assertFalse($result->isHealthy());
        $this->assertSame(['test' => false], $result->getDetails());
    }

    public function testCheckingFailingMultipleConnectionsWithFailFastOn()
    {
        config()->set('cache.stores', ['first' => [], 'second' => []]);

        Cache::shouldReceive('store')
            ->once()
            ->withArgs(['first'])
            ->andThrow(new RuntimeException('Test exception - first'));

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $args) {
                if ("Cache store 'first' status check failed" != $message) {
                    return false;
                }
                if ('first' != $args['store']) {
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

        $checker = new CacheStatusChecker();
        $result = $checker->check();

        $this->assertFalse($result->isHealthy());
        $this->assertSame(['first' => false], $result->getDetails());
    }

    public function testCheckingFailingMultipleConnectionsWithoutFailFast()
    {
        config()->set('cache.stores', ['first' => [], 'second' => []]);

        Cache::shouldReceive('store')
            ->once()
            ->withArgs(['first'])
            ->andThrow(new RuntimeException('Test exception - first'));

        Cache::shouldReceive('store')
            ->once()
            ->withArgs(['second'])
            ->andThrow(new RuntimeException('Test exception - second'));

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $args) {
                if ("Cache store 'first' status check failed" != $message) {
                    return false;
                }
                if ('first' != $args['store']) {
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
                if ("Cache store 'second' status check failed" != $message) {
                    return false;
                }
                if ('second' != $args['store']) {
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

        $checker = new CacheStatusChecker();
        $result = $checker->check(failFast: false);

        $this->assertFalse($result->isHealthy());
        $this->assertSame(['first' => false, 'second' => false], $result->getDetails());
    }

    public function testCheckingPassingSingleConnection()
    {
        config()->set('cache.stores', ['first' => []]);

        $store = $this->mock(CacheInterface::class);

        $store->expects('get')
            ->once()
            ->withAnyArgs()
            ->andReturnTrue();

        Cache::shouldReceive('store')
            ->once()
            ->withArgs(['first'])
            ->andReturn($store);

        $checker = new CacheStatusChecker();
        $result = $checker->check();

        $this->assertTrue($result->isHealthy());
        $this->assertSame(['first' => true], $result->getDetails());
    }

    public function testCheckingFailOneThenPassAnother()
    {
        config()->set('cache.stores', ['first' => [], 'second' => []]);

        $first = $this->mock(CacheInterface::class);
        $first->expects('get')
            ->once()
            ->withAnyArgs()
            ->andThrow(new RuntimeException('Failing first ping'));

        $second = $this->mock(CacheInterface::class);
        $second->expects('get')
            ->once()
            ->withAnyArgs()
            ->andReturnTrue();

        Cache::shouldReceive('store')
            ->once()
            ->withArgs(['first'])
            ->andReturn($first);

        Cache::shouldReceive('store')
            ->once()
            ->withArgs(['second'])
            ->andReturn($second);

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $args) {
                if ("Cache store 'first' status check failed" != $message) {
                    return false;
                }
                if ('first' != $args['store']) {
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

        $checker = new CacheStatusChecker();
        $result = $checker->check(failFast: false);

        $this->assertFalse($result->isHealthy());
        $this->assertSame(['first' => false, 'second' => true], $result->getDetails());
    }

    public function testCheckIsDoneByRetrievingGeneratedKey()
    {
        config()->set('cache.stores', ['test' => []]);

        /** @var \Mockery\MockInterface|CacheInterface $store */
        $store = $this->mock(CacheInterface::class);

        $store->expects('get')
            ->once()
            ->withArgs(function ($key) {
                $prefix = 'pinepain/laravel-system-info.check.cache.';
                if (!str_starts_with($key, $prefix)) {
                    return false;
                }
                $timestamp = str_replace($prefix, '', $key);

                return abs(time() - $timestamp) < 3;
            })
            ->andReturnTrue();

        Cache::shouldReceive('store')
            ->once()
            ->withArgs(['test'])
            ->andReturn($store);

        $checker = new CacheStatusChecker();
        $result = $checker->check();

        $this->assertTrue($result->isHealthy());
        $this->assertSame(['test' => true], $result->getDetails());
    }
}
