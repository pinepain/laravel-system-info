<?php declare(strict_types=1);

namespace Integration\Checkers;


use Orchestra\Testbench\TestCase;
use Pinepain\SystemInfo\Checkers\DatabasesStatusChecker;


class DatabasesStatusCheckerTest extends TestCase
{
    public function testCheckTwoSuccessfulConnections()
    {
        config()->set('database.connections', [
            'first' => ['host' => env('DB_HOST', '127.0.0.1'), 'driver' => 'pgsql', 'port' => '5432', 'username' => 'postgres', 'password' => 'rootpswd', 'database' => 'postgres'],
            'second' => ['host' => env('DB_HOST', '127.0.0.1'), 'driver' => 'pgsql', 'port' => '5432', 'username' => 'postgres', 'password' => 'rootpswd', 'database' => 'postgres'],
        ]);

        $checker = new DatabasesStatusChecker();
        $result = $checker->check();

        $this->assertTrue($result->isHealthy());
        $this->assertSame(['first' => true, 'second' => true], $result->getDetails());
    }

    public function testCheckOneFailingOneSuccessfulWithFailFastDefault()
    {
        config()->set('database.connections', [
            'first' => ['host' => env('DB_HOST', '127.0.0.1'), 'driver' => 'pgsql', 'port' => '5432', 'username' => 'postgres', 'password' => 'bad', 'database' => 'postgres'],
            'second' => ['host' => env('DB_HOST', '127.0.0.1'), 'driver' => 'pgsql', 'port' => '5432', 'username' => 'postgres', 'password' => 'rootpswd', 'database' => 'postgres'],
        ]);

        $checker = new DatabasesStatusChecker();
        $result = $checker->check();

        $this->assertFalse($result->isHealthy());
        $this->assertSame(['first' => false], $result->getDetails());
    }

    public function testCheckOneFailingOneSuccessfulWithFailFastSetToFalse()
    {
        config()->set('database.connections', [
            'first' => ['host' => env('DB_HOST', '127.0.0.1'), 'driver' => 'pgsql', 'port' => '5432', 'username' => 'postgres', 'password' => 'bad', 'database' => 'postgres'],
            'second' => ['host' => env('DB_HOST', '127.0.0.1'), 'driver' => 'pgsql', 'port' => '5432', 'username' => 'postgres', 'password' => 'rootpswd', 'database' => 'postgres'],
        ]);

        $checker = new DatabasesStatusChecker();
        $result = $checker->check(failFast: false);

        $this->assertFalse($result->isHealthy());
        $this->assertSame(['first' => false, 'second' => true], $result->getDetails());
    }
}
