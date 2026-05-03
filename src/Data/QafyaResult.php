<?php

declare(strict_types=1);

namespace Mosab\QafyaDetector\Data;

use JsonSerializable;

/**
 * Immutable word/ending-level qafya result.
 *
 * Public object properties expose important response sections as value objects
 * where a stable shape matters (segment/pattern). The array form remains
 * available for HTTP APIs, JSON serialization, and persistence.
 */
final readonly class QafyaResult implements JsonSerializable
{
    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $qafya
     * @param  array<string, mixed>  $signatures
     * @param  array<string, mixed>  $confidence
     * @param  array<string, mixed>  $diagnostics
     * @param  list<array<string, mixed>>  $trace
     */
    public function __construct(
        public string $status,
        public array $input,
        public QafyaSegment $segment,
        public array $qafya,
        public array $signatures,
        public QafyaPattern $pattern,
        public array $confidence,
        public array $diagnostics,
        public array $trace,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $qafya = self::arrayValue($data['qafya'] ?? []);
        $segment = self::arrayValue($data['segment'] ?? ($qafya['segment'] ?? ($qafya['boundary'] ?? [])));
        $pattern = self::arrayValue($data['pattern'] ?? ($qafya['pattern'] ?? []));

        return new self(
            status: is_string($data['status'] ?? null) ? $data['status'] : 'partial',
            input: self::arrayValue($data['input'] ?? []),
            segment: QafyaSegment::fromArray($segment),
            qafya: $qafya,
            signatures: self::arrayValue($data['signatures'] ?? []),
            pattern: QafyaPattern::fromArray($pattern),
            confidence: self::arrayValue($data['confidence'] ?? []),
            diagnostics: self::arrayValue($data['diagnostics'] ?? []),
            trace: self::listOfArrays($data['trace'] ?? []),
        );
    }

    public function status(): string
    {
        return $this->status;
    }

    public function inputText(): string
    {
        return (string) ($this->input['text'] ?? '');
    }

    public function normalizedText(): string
    {
        return (string) ($this->input['normalized'] ?? '');
    }

    public function mode(): string
    {
        return (string) ($this->input['mode']['resolved'] ?? 'heuristic');
    }

    public function category(): string
    {
        return (string) ($this->qafya['category'] ?? 'unknown');
    }

    public function subtype(): ?string
    {
        return $this->stringFrom($this->qafya, 'subtype');
    }

    public function name(): ?string
    {
        return $this->stringFrom($this->qafya, 'name');
    }

    public function rawi(): ?string
    {
        return $this->componentLetter('rawi');
    }

    public function rawiHaraka(): ?string
    {
        return $this->componentString('rawi', 'haraka');
    }

    public function rawiHarakaName(): ?string
    {
        return $this->componentString('rawi', 'haraka_name');
    }

    public function mujra(): ?string
    {
        return $this->motion('mujra');
    }

    public function radf(): ?string
    {
        return $this->componentLetter('radf');
    }

    public function wasl(): ?string
    {
        return $this->componentLetter('wasl');
    }

    public function khurooj(): ?string
    {
        return $this->componentLetter('khurooj');
    }

    public function taasis(): ?string
    {
        return $this->componentLetter('taasis');
    }

    public function dakhiil(): ?string
    {
        return $this->componentLetter('dakhiil');
    }

    public function segmentSurface(): ?string
    {
        return $this->segment->surface !== '' ? $this->segment->surface : null;
    }

    public function segmentArudic(): ?string
    {
        return $this->segment->arudic !== '' ? $this->segment->arudic : null;
    }

    public function segmentMethod(): string
    {
        return $this->segment->method;
    }

    public function isCompleteBoundary(): bool
    {
        return $this->segment->complete;
    }

    public function patternSurface(): ?string
    {
        return $this->pattern->surface;
    }

    public function qafyaPattern(): ?string
    {
        return $this->patternSurface();
    }

    public function qafyaSegment(): ?string
    {
        return $this->segmentSurface();
    }

    public function componentSignature(): ?string
    {
        return $this->stringFrom($this->signatures, 'component');
    }

    public function strictSignature(): ?string
    {
        return $this->stringFrom($this->signatures, 'strict');
    }

    public function clusterSignature(): ?string
    {
        return $this->stringFrom($this->signatures, 'cluster');
    }

    public function visualSignature(): ?string
    {
        return $this->stringFrom($this->signatures, 'visual');
    }

    public function confidenceScore(): float
    {
        return (float) ($this->confidence['score'] ?? 0.0);
    }

    public function confidenceLevel(): string
    {
        return (string) ($this->confidence['level'] ?? 'none');
    }

    /** @return array<string, mixed> */
    public function segment(): array
    {
        return $this->segment->toArray();
    }

    /** @return array<string, mixed> */
    public function pattern(): array
    {
        return $this->pattern->toArray();
    }

    /** @return array<string, mixed> */
    public function components(): array
    {
        return self::arrayValue($this->qafya['components'] ?? []);
    }

    /** @return array<string, mixed> */
    public function motions(): array
    {
        return self::arrayValue($this->qafya['motions'] ?? []);
    }

    /** @return list<array<string, mixed>> */
    public function defects(): array
    {
        return self::listOfArrays($this->diagnostics['defects'] ?? []);
    }

    /** @return list<array<string, mixed>> */
    public function warnings(): array
    {
        return self::listOfArrays($this->diagnostics['warnings'] ?? []);
    }

    public function hasDefects(): bool
    {
        return $this->defects() !== [];
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $segment = $this->segment->toArray();
        $pattern = $this->pattern->toArray();
        $qafya = $this->qafya;
        $qafya['segment'] = $segment;
        $qafya['boundary'] = $segment;
        $qafya['pattern'] = $pattern;

        return [
            'status' => $this->status,
            'input' => $this->input,
            'segment' => $segment,
            'qafya' => $qafya,
            'signatures' => $this->signatures,
            'pattern' => $pattern,
            'confidence' => $this->confidence,
            'diagnostics' => $this->diagnostics,
            'trace' => $this->trace,
        ];
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    private function componentLetter(string $component): ?string
    {
        return $this->componentString($component, 'letter');
    }

    private function componentString(string $component, string $key): ?string
    {
        $components = $this->components();
        $part = self::arrayValue($components[$component] ?? []);

        return $this->stringFrom($part, $key);
    }

    private function motion(string $key): ?string
    {
        return $this->stringFrom($this->motions(), $key);
    }

    /** @param array<string, mixed> $array */
    private function stringFrom(array $array, string $key): ?string
    {
        $value = $array[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    /** @return array<string, mixed> */
    private static function arrayValue(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }

    /** @return list<array<string, mixed>> */
    private static function listOfArrays(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, 'is_array'));
    }
}
