<?php declare(strict_types=1);

namespace Pinepain\SystemInfo\Checkers;


use JsonSerializable;


class Result implements JsonSerializable
{
    private bool $healthy;
    private array $details;

    public function __construct(bool $healthy, array $details = [])
    {
        $this->healthy = $healthy;
        $this->details = $details;
    }

    public function isHealthy(): bool
    {
        return $this->healthy;
    }

    public function getDetails(): array
    {
        return $this->details;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'healthy' => $this->healthy,
            'details' => $this->details,
        ];
    }
}
