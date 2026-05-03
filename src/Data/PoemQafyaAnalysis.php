<?php

declare(strict_types=1);

namespace Mosab\QafyaDetector\Data;

use JsonSerializable;

/**
 * Immutable poem-level qafya analysis result.
 *
 * Important distinction:
 * - $dominant is authoritative qafya only. It is null for genuinely mixed poems.
 * - $indexing contains most-frequent cluster statistics for browsing/filtering.
 */
final readonly class PoemQafyaAnalysis implements JsonSerializable
{
    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $obligations
     * @param  array<string, mixed>  $indexing
     * @param  list<array<string, mixed>>  $clusters
     * @param  list<array<string, mixed>>  $defects
     * @param  list<array<string, mixed>>  $sanad
     * @param  list<array<string, mixed>>  $endings
     * @param  array<string, mixed>  $diagnostics
     */
    public function __construct(
        public string $status,
        public ?string $error,
        public array $input,
        public ?PoemReference $reference,
        public PoemSummary $summary,
        public ?DominantQafya $dominant,
        public array $obligations,
        public array $indexing,
        public array $clusters,
        public array $defects,
        public array $sanad,
        public array $endings,
        public array $diagnostics,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $reference = $data['reference'] ?? null;
        $dominant = $data['dominant'] ?? null;

        return new self(
            status: is_string($data['status'] ?? null) ? $data['status'] : 'partial',
            error: is_string($data['error'] ?? null) ? $data['error'] : null,
            input: self::arrayValue($data['input'] ?? []),
            reference: is_array($reference) ? PoemReference::fromArray($reference) : null,
            summary: PoemSummary::fromArray(self::arrayValue($data['summary'] ?? [])),
            dominant: is_array($dominant) ? DominantQafya::fromArray($dominant) : null,
            obligations: self::arrayValue($data['obligations'] ?? []),
            indexing: self::arrayValue($data['indexing'] ?? []),
            clusters: self::listOfArrays($data['clusters'] ?? []),
            defects: self::listOfArrays($data['defects'] ?? []),
            sanad: self::listOfArrays($data['sanad'] ?? []),
            endings: self::listOfArrays($data['endings'] ?? []),
            diagnostics: self::arrayValue($data['diagnostics'] ?? []),
        );
    }

    public function status(): string
    {
        return $this->status;
    }

    public function error(): ?string
    {
        return $this->error;
    }

    public function isConsistent(): bool
    {
        return $this->summary->isConsistent;
    }

    public function hasMultipleQafyas(): bool
    {
        return $this->summary->hasMultipleQafyas;
    }

    public function isMultipleQafya(): bool
    {
        return $this->summary->hasMultipleQafyas;
    }

    public function dominantIsAuthoritative(): bool
    {
        return $this->summary->dominantIsAuthoritative;
    }

    public function baytCount(): int
    {
        return (int) ($this->input['bayt_count'] ?? 0);
    }

    public function endingsCount(): int
    {
        return count($this->endings);
    }

    public function dominantRatio(): float
    {
        return $this->summary->dominantRatio;
    }

    public function rawi(): ?string
    {
        return $this->dominant?->rawi;
    }

    public function rawiHaraka(): ?string
    {
        return $this->dominant?->rawiHaraka;
    }

    public function qafyaType(): ?string
    {
        return $this->summary->qafyaCategory;
    }

    public function qafyaName(): ?string
    {
        return $this->summary->qafyaName;
    }

    public function qafyaPattern(): ?string
    {
        return $this->dominant?->pattern;
    }

    public function qafyaSegment(): ?string
    {
        return $this->dominant?->segment;
    }

    public function hasDefects(): bool
    {
        return $this->defects !== [];
    }

    public function mostFrequentRawi(): ?string
    {
        return self::stringOrNull($this->indexing['most_frequent_rawi'] ?? null);
    }

    public function mostFrequentSignature(): ?string
    {
        return self::stringOrNull($this->indexing['most_frequent_signature'] ?? null);
    }

    public function mostFrequentPattern(): ?string
    {
        return self::stringOrNull($this->indexing['most_frequent_pattern'] ?? null);
    }

    public function mostFrequentSegment(): ?string
    {
        return self::stringOrNull($this->indexing['most_frequent_segment'] ?? null);
    }

    public function mostFrequentRatio(): float
    {
        return (float) ($this->indexing['most_frequent_ratio'] ?? 0.0);
    }

    public function usableForFiltering(): bool
    {
        return (bool) ($this->indexing['usable_for_filtering'] ?? false);
    }

    /** @return array<string, mixed>|null */
    public function reference(): ?array
    {
        return $this->reference?->toArray();
    }

    /** @return array<string, mixed> */
    public function summary(): array
    {
        return $this->summary->toArray();
    }

    /** @return array<string, mixed>|null */
    public function dominant(): ?array
    {
        return $this->dominant?->toArray();
    }

    /** @return array<string, mixed> */
    public function obligations(): array
    {
        return $this->obligations;
    }

    /** @return array<string, mixed> */
    public function indexing(): array
    {
        return $this->indexing;
    }

    /** @return list<array<string, mixed>> */
    public function clusters(): array
    {
        return $this->clusters;
    }

    /** @return list<array<string, mixed>> */
    public function defects(): array
    {
        return $this->defects;
    }

    /** @return list<array<string, mixed>> */
    public function sanad(): array
    {
        return $this->sanad;
    }

    /** @return list<array<string, mixed>> */
    public function endings(): array
    {
        return $this->endings;
    }

    /** @return list<array<string, mixed>> */
    public function defectsByType(string $type): array
    {
        return array_values(array_filter($this->defects, static fn (array $defect): bool => ($defect['type'] ?? null) === $type));
    }

    /** @return array<string, mixed>|null */
    public function endingAtBayt(int $bayt): ?array
    {
        foreach ($this->endings as $ending) {
            if (($ending['bayt'] ?? null) === $bayt) {
                return $ending;
            }
        }

        return null;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'error' => $this->error,
            'input' => $this->input,
            'reference' => $this->reference?->toArray(),
            'summary' => $this->summary->toArray(),
            'dominant' => $this->dominant?->toArray(),
            'obligations' => $this->obligations,
            'indexing' => $this->indexing,
            'clusters' => $this->clusters,
            'defects' => $this->defects,
            'sanad' => $this->sanad,
            'endings' => $this->endings,
            'diagnostics' => $this->diagnostics,
        ];
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return $this->toArray();
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

    private static function stringOrNull(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
