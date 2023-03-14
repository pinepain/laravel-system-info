<?php declare(strict_types=1);

namespace Pinepain\SystemInfo\Tests\Unit\Checkers;


use Orchestra\Testbench\TestCase;
use Pinepain\SystemInfo\Checkers\CheckerInterface;
use Pinepain\SystemInfo\Checkers\VersionChecker;


class VersionCheckerTest extends TestCase
{
    public function testInstantiableAndImplementsInterface()
    {
        $checker = new VersionChecker();

        $this->assertInstanceOf(CheckerInterface::class, $checker);
        $this->assertSame('version', $checker->getName());
    }

    public function testEmptyVersionIsNotHealthy()
    {
        config()->set('app', []);
        config()->set('system-info.version', []);

        $checker = new VersionChecker();
        $result = $checker->check();

        $this->assertFalse($result->isHealthy());
        $this->assertEmpty($result->getDetails());
    }

    public function testEmptyCustomVersion()
    {
        config()->set('app', ['name' => 'test name', 'env' => 'test env']);
        config()->set('system-info.version', []);

        $checker = new VersionChecker();
        $result = $checker->check();

        $this->assertTrue($result->isHealthy());
        $details = [
            'name' => 'test name',
            'env' => 'test env',
        ];
        $this->assertSame($details, $result->getDetails());
    }

    public function testCustomVersionNotEmpty()
    {
        config()->set('app', ['name' => 'test name', 'env' => 'test env']);
        config()->set('system-info.version', ['key' => 'value', 'empty' => '', 'zero' => 0, 'null' => null]);

        $checker = new VersionChecker();
        $result = $checker->check();

        $this->assertTrue($result->isHealthy());
        $details = [
            'name' => 'test name',
            'env' => 'test env',
            'key' => 'value',
        ];
        $this->assertSame($details, $result->getDetails());
    }
}
