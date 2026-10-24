<?php

declare(strict_types=1);

namespace Mosab\QafyaDetector\Data;

use JsonSerializable;

/** Immutable, explicitly external linguistic suggestions. */
final readonly class LinguisticEvidence implements JsonSerializable
{
    /**
     * @param  list<array<string, mixed>>  $suggestions
     * @param  list<array<string, mixed>>  $morphology
     * @param  list<string>  $warnings
     */
    public function __construct(
        public string $provider,
        public array $suggestions = [],
        public array $morphology = [],
        public ?float $confidence = null,
        public array $warnings = [],
    ) {
        if ($confidence !== null && ($confidence < 0.0 || $confidence > 1.0)) {
            throw new \InvalidArgumentException('Linguistic evidence confidence must be between 0 and 1.');
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'provider' => $this->provider,
            'suggestions' => $this->suggestions,
            'morphology' => $this->morphology,
            'confidence' => $this->confidence,
            'confidence_semantics' => 'provider_reported_score_not_verified_probability',
            'used_to_override_qafya' => false,
            'warnings' => $this->warnings,
        ];
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
