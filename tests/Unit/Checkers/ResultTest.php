<?php declare(strict_types=1);

namespace Pinepain\SystemInfo\Tests\Unit\Checkers;


use PHPUnit\Framework\TestCase;
use Pinepain\SystemInfo\Checkers\Result;


class ResultTest extends TestCase
{
    /**
     * @dataProvider variationsProvider
     */
    public function testVariations(bool $status, array $details)
    {
        $result = new Result($status, $details);

        $this->assertSame($status, $result->isHealthy());
        $this->assertSame($details, $result->getDetails());
        $this->assertSame([
            'healthy' => $status,
            'details' => $details,
        ], $result->jsonSerialize());
    }

    public static function variationsProvider(): array
    {
        return [
            [true, []],
            [false, []],
            [true, ['some' => 'data', 'might', 'be' => ['here']]],
            [false, ['in' => 'case', 'of', 'error' => ['too']]],
        ];
    }
}
