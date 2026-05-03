<?php

declare(strict_types=1);

namespace Mosab\QafyaDetector\Data;

use JsonSerializable;

final readonly class DominantQafya implements JsonSerializable
{
    public function __construct(
        public ?string $rawi,
        public ?string $rawiHaraka,
        public ?string $mujra,
        public ?string $radf,
        public ?string $taasis,
        public ?string $dakhiil,
        public ?string $wasl,
        public ?string $khurooj,
        public ?string $segment,
        public ?string $pattern,
        public ?string $signature,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            rawi: self::stringOrNull($data['rawi'] ?? null),
            rawiHaraka: self::stringOrNull($data['rawi_haraka'] ?? null),
            mujra: self::stringOrNull($data['mujra'] ?? null),
            radf: self::stringOrNull($data['radf'] ?? null),
            taasis: self::stringOrNull($data['taasis'] ?? null),
            dakhiil: self::stringOrNull($data['dakhiil'] ?? null),
            wasl: self::stringOrNull($data['wasl'] ?? null),
            khurooj: self::stringOrNull($data['khurooj'] ?? null),
            segment: self::stringOrNull($data['segment'] ?? null),
            pattern: self::stringOrNull($data['pattern'] ?? null),
            signature: self::stringOrNull($data['signature'] ?? null),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'rawi' => $this->rawi,
            'rawi_haraka' => $this->rawiHaraka,
            'mujra' => $this->mujra,
            'radf' => $this->radf,
            'taasis' => $this->taasis,
            'dakhiil' => $this->dakhiil,
            'wasl' => $this->wasl,
            'khurooj' => $this->khurooj,
            'segment' => $this->segment,
            'pattern' => $this->pattern,
            'signature' => $this->signature,
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
