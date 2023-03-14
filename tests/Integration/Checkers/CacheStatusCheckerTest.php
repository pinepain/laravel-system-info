<?php declare(strict_types=1);

namespace Integration\Checkers;


use Orchestra\Testbench\TestCase;
use Pinepain\SystemInfo\Checkers\CacheStatusChecker;


class CacheStatusCheckerTest extends TestCase
{
    public function testCheckTwoSuccessfulConnections()
    {
        config()->set('cache.stores', ['first' => ['driver' => 'array'], 'second' => ['driver' => 'file', 'path' => storage_path('/tmp/cache/data')]]);

        $checker = new CacheStatusChecker();
        $result = $checker->check();

        $this->assertTrue($result->isHealthy());
        $this->assertSame(['first' => true, 'second' => true], $result->getDetails());
    }

    public function testCheckOneFailingOneSuccessfulWithFailFastDefault()
    {
        config()->set('cache.stores', ['first' => ['driver' => 'baddriver'], 'second' => ['driver' => 'array']]);

        $checker = new CacheStatusChecker();
        $result = $checker->check();

        $this->assertFalse($result->isHealthy());
        $this->assertSame(['first' => false], $result->getDetails());
    }

    public function testCheckOneFailingOneSuccessfulWithFailFastSetToFalse()
    {
        config()->set('cache.stores', ['first' => ['driver' => 'baddriver'], 'second' => ['driver' => 'array']]);

        $checker = new CacheStatusChecker();
        $result = $checker->check(failFast: false);

        $this->assertFalse($result->isHealthy());
        $this->assertSame(['first' => false, 'second' => true], $result->getDetails());
    }
}
