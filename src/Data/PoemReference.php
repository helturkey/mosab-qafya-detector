<?php

declare(strict_types=1);

namespace Mosab\QafyaDetector\Data;

use JsonSerializable;

final readonly class PoemReference implements JsonSerializable
{
    public function __construct(
        public string $source,
        public int $bayt,
        public string $position,
        public string $ending,
        public ?string $signature,
        public ?string $segment,
        public ?string $pattern,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            source: is_string($data['source'] ?? null) ? $data['source'] : 'unknown',
            bayt: is_int($data['bayt'] ?? null) ? $data['bayt'] : (int) ($data['bayt'] ?? 0),
            position: is_string($data['position'] ?? null) ? $data['position'] : 'ajz',
            ending: is_string($data['ending'] ?? null) ? $data['ending'] : '',
            signature: self::stringOrNull($data['signature'] ?? null),
            segment: self::stringOrNull($data['segment'] ?? null),
            pattern: self::stringOrNull($data['pattern'] ?? null),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'source' => $this->source,
            'bayt' => $this->bayt,
            'position' => $this->position,
            'ending' => $this->ending,
            'signature' => $this->signature,
            'segment' => $this->segment,
            'pattern' => $this->pattern,
        ];
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    private static function stringOrNull(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
