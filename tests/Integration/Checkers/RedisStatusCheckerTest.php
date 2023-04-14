<?php declare(strict_types=1);

namespace Pinepain\SystemInfo\Tests\Integration\Checkers;


use Orchestra\Testbench\TestCase;
use Pinepain\SystemInfo\Checkers\RedisStatusChecker;


class RedisStatusCheckerTest extends TestCase
{
    public function testCheckTwoSuccessfulConnections()
    {
        config()->set('database.redis', [
            'client' => 'predis',
            'first' => ['host' => env('REDIS_HOST', '127.0.0.1'), 'port' => '6379', 'database' => 0],
            'second' => ['host' => env('REDIS_HOST', '127.0.0.1'), 'port' => '6379', 'database' => 1],
        ]);
        config()->set('database.redis.client', 'predis');

        $checker = new RedisStatusChecker();
        $result = $checker->check();

        $this->assertTrue($result->isHealthy());
        $this->assertSame(['first' => true, 'second' => true], $result->getDetails());
    }

    public function testCheckOneFailingOneSuccessfulWithFailFastDefault()
    {
        config()->set('database.redis', [
            'client' => 'predis',
            'first' => ['host' => 'badhost', 'port' => 'badport', 'database' => 0],
            'second' => ['host' => env('REDIS_HOST', '127.0.0.1'), 'port' => '6379', 'database' => 1],
        ]);
        config()->set('database.redis.client', 'predis');

        $checker = new RedisStatusChecker();
        $result = $checker->check();

        $this->assertFalse($result->isHealthy());
        $this->assertSame(['first' => false], $result->getDetails());
    }

    public function testCheckOneFailingOneSuccessfulWithFailFastSetToFalse()
    {
        config()->set('database.redis', [
            'client' => 'predis',
            'first' => ['host' => 'badhost', 'port' => 'badport', 'database' => 0],
            'second' => ['host' => env('REDIS_HOST', '127.0.0.1'), 'port' => '6379', 'database' => 1],
        ]);
        config()->set('database.redis.client', 'predis');

        $checker = new RedisStatusChecker();
        $result = $checker->check(failFast: false);

        $this->assertFalse($result->isHealthy());
        $this->assertSame(['first' => false, 'second' => true], $result->getDetails());
    }
}
