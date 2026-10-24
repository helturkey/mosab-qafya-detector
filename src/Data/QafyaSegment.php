<?php

declare(strict_types=1);

namespace Mosab\QafyaDetector\Data;

use JsonSerializable;

/** Immutable qafya boundary segment linked to the original input when available. */
final readonly class QafyaSegment implements JsonSerializable
{
    /**
     * @param  list<string>  $warnings
     * @param  array{start_index:?int,end_index:?int,byte_start:?int,byte_end:?int}|null  $sourceSpan
     */
    public function __construct(
        public string $surface,
        public string $arudic,
        public string $method,
        public bool $complete,
        public ?int $movingCountBetweenSakins,
        public ?int $lastSakinIndex,
        public ?int $previousSakinIndex,
        public ?int $startIndex,
        public array $warnings = [],
        public ?array $sourceSpan = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $sourceSpan = is_array($data['source_span'] ?? null) ? $data['source_span'] : null;

        return new self(
            surface: self::str($data['surface'] ?? null),
            arudic: self::str($data['arudic'] ?? null),
            method: self::str($data['method'] ?? null, 'unknown'),
            complete: (bool) ($data['complete'] ?? false),
            movingCountBetweenSakins: self::intOrNull($data['moving_count_between_sakins'] ?? null),
            lastSakinIndex: self::intOrNull($data['last_sakin_index'] ?? null),
            previousSakinIndex: self::intOrNull($data['previous_sakin_index'] ?? null),
            startIndex: self::intOrNull($data['start_index'] ?? null),
            warnings: self::listOfStrings($data['warnings'] ?? []),
            sourceSpan: $sourceSpan === null ? null : [
                'start_index' => self::intOrNull($sourceSpan['start_index'] ?? null),
                'end_index' => self::intOrNull($sourceSpan['end_index'] ?? null),
                'byte_start' => self::intOrNull($sourceSpan['byte_start'] ?? null),
                'byte_end' => self::intOrNull($sourceSpan['byte_end'] ?? null),
            ],
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'surface' => $this->surface,
            'arudic' => $this->arudic,
            'method' => $this->method,
            'complete' => $this->complete,
            'moving_count_between_sakins' => $this->movingCountBetweenSakins,
            'last_sakin_index' => $this->lastSakinIndex,
            'previous_sakin_index' => $this->previousSakinIndex,
            'start_index' => $this->startIndex,
            'warnings' => $this->warnings,
            'source_span' => $this->sourceSpan,
        ];
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    private static function str(mixed $value, string $default = ''): string
    {
        return is_string($value) ? $value : $default;
    }

    private static function intOrNull(mixed $value): ?int
    {
        return is_int($value) ? $value : (is_numeric($value) ? (int) $value : null);
    }

    /** @return list<string> */
    private static function listOfStrings(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, 'is_string'));
    }
}
