<?php declare(strict_types=1);

namespace Pinepain\SystemInfo\Tests\Unit\Checkers;


use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Orchestra\Testbench\TestCase;
use Pinepain\SystemInfo\Checkers\CheckerInterface;
use Pinepain\SystemInfo\Checkers\SessionStatusChecker;
use SessionHandlerInterface;


class SessionStatusCheckerTest extends TestCase
{
    public function testInstantiableAndImplementsInterface()
    {
        $checker = new SessionStatusChecker();

        $this->assertInstanceOf(CheckerInterface::class, $checker);
        $this->assertSame('session', $checker->getName());
    }

    public function testCheckingWrongDefaultDriverFails()
    {
        config()->set('session.driver', 'nonesixtent');

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $args) {
                if ('Session status check failed' != $message) {
                    return false;
                }
                if ($args['e'] != 'Driver [nonesixtent] not supported.') {
                    return false;
                }
                if ($args['class'] != 'InvalidArgumentException') {
                    return false;
                }
                if (!str_starts_with($args['trace'], '#0')) {
                    return false;
                }

                return true;
            });

        $checker = new SessionStatusChecker();
        $result = $checker->check();

        $this->assertFalse($result->isHealthy());
        $this->assertEmpty($result->getDetails());
    }

    public function testCheckingMisconfiguredDriverFails()
    {
        config()->set('session.driver', 'database');
        config()->set('session.connection', 'nonexistent');
        config()->set('database.connections', [
            'nonexistent' => [
                'driver' => 'mysql',
                'database' => 'does not matter',
                'host' => 'localhost',
                'password' => 'password does not matter',
            ],
        ]);

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $args) {
                if ('Session status check failed' != $message) {
                    return false;
                }
                if (!str_starts_with($args['e'], 'SQLSTATE[HY000] [2002] No such file or directory')) {
                    return false;
                }
                if ($args['class'] != 'Illuminate\Database\QueryException') {
                    return false;
                }
                if (!str_starts_with($args['trace'], '#0')) {
                    return false;
                }

                return true;
            });

        $checker = new SessionStatusChecker();
        $result = $checker->check();

        $this->assertFalse($result->isHealthy());
        $this->assertEmpty($result->getDetails());
    }

    public function testConfiguredSessionDriver()
    {
        config()->set('session.driver', 'array');

        $checker = new SessionStatusChecker();
        $result = $checker->check();

        $this->assertTrue($result->isHealthy());
        $this->assertEmpty($result->getDetails());
    }

    public function testCheckIsDoneByRetrievingGeneratedKey()
    {
        /** @var \Mockery\MockInterface|SessionHandlerInterface $handler */
        $handler = $this->mock(SessionHandlerInterface::class);
        $handler->expects('read')
            ->once()
            ->withArgs(function ($key) {
                $prefix = 'pinepain/laravel-system-info.check.session.';
                if (!str_starts_with($key, $prefix)) {
                    return false;
                }
                $timestamp = str_replace($prefix, '', $key);

                return abs(time() - $timestamp) < 3;
            })
            ->andReturnTrue();

        Session::setHandler($handler);

        $checker = new SessionStatusChecker();
        $result = $checker->check();

        $this->assertTrue($result->isHealthy());
        $this->assertEmpty($result->getDetails());
    }

}
