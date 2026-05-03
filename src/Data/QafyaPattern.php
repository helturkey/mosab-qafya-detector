<?php

declare(strict_types=1);

namespace Mosab\QafyaDetector\Data;

use JsonSerializable;

/**
 * Immutable qafya pattern/signature projection.
 */
final readonly class QafyaPattern implements JsonSerializable
{
    public function __construct(
        public ?string $surface,
        public ?string $component,
        public ?string $strict,
        public ?string $cluster,
        public ?string $canonical,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            surface: self::stringOrNull($data['surface'] ?? null),
            component: self::stringOrNull($data['component'] ?? null),
            strict: self::stringOrNull($data['strict'] ?? null),
            cluster: self::stringOrNull($data['cluster'] ?? null),
            canonical: self::stringOrNull($data['canonical'] ?? null),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'surface' => $this->surface,
            'component' => $this->component,
            'strict' => $this->strict,
            'cluster' => $this->cluster,
            'canonical' => $this->canonical,
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
