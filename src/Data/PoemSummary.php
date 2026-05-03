<?php

declare(strict_types=1);

namespace Mosab\QafyaDetector\Data;

use JsonSerializable;

final readonly class PoemSummary implements JsonSerializable
{
    public function __construct(
        public ?string $qafyaCategory,
        public ?string $qafyaSubtype,
        public ?string $qafyaName,
        public ?string $qafyaSegment,
        public ?string $qafyaPattern,
        public bool $isConsistent,
        public bool $hasMultipleQafyas,
        public bool $dominantIsAuthoritative,
        public float $dominantRatio,
        public float $confidence,
        public int $defectsCount,
        public int $sanadCount,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $isConsistent = (bool) ($data['is_consistent'] ?? false);

        return new self(
            qafyaCategory: self::stringOrNull($data['qafya_category'] ?? null),
            qafyaSubtype: self::stringOrNull($data['qafya_subtype'] ?? null),
            qafyaName: self::stringOrNull($data['qafya_name'] ?? null),
            qafyaSegment: self::stringOrNull($data['qafya_segment'] ?? null),
            qafyaPattern: self::stringOrNull($data['qafya_pattern'] ?? null),
            isConsistent: $isConsistent,
            hasMultipleQafyas: (bool) ($data['has_multiple_qafyas'] ?? ! $isConsistent),
            dominantIsAuthoritative: (bool) ($data['dominant_is_authoritative'] ?? false),
            dominantRatio: (float) ($data['dominant_ratio'] ?? 0.0),
            confidence: (float) ($data['confidence'] ?? 0.0),
            defectsCount: (int) ($data['defects_count'] ?? 0),
            sanadCount: (int) ($data['sanad_count'] ?? 0),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'qafya_category' => $this->qafyaCategory,
            'qafya_subtype' => $this->qafyaSubtype,
            'qafya_name' => $this->qafyaName,
            'qafya_segment' => $this->qafyaSegment,
            'qafya_pattern' => $this->qafyaPattern,
            'is_consistent' => $this->isConsistent,
            'has_multiple_qafyas' => $this->hasMultipleQafyas,
            'dominant_is_authoritative' => $this->dominantIsAuthoritative,
            'dominant_ratio' => $this->dominantRatio,
            'confidence' => $this->confidence,
            'defects_count' => $this->defectsCount,
            'sanad_count' => $this->sanadCount,
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
