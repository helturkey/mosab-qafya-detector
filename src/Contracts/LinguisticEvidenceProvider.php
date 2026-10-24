<?php

declare(strict_types=1);

namespace Mosab\QafyaDetector\Contracts;

use Mosab\QafyaDetector\Data\LinguisticEvidence;

/**
 * Optional adapter for an Arabic diacritizer or morphological analyzer.
 * Returned evidence is diagnostic only; it must not rewrite qafya decisions.
 */
interface LinguisticEvidenceProvider
{
    /** @param array<string, mixed> $context */
    public function analyze(string $text, array $context = []): LinguisticEvidence;
}
