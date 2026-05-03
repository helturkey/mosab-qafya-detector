<?php

declare(strict_types=1);

namespace Mosab\QafyaDetector\Analysis;

/**
 * Extracts sanad-related defects into a dedicated list.
 */
final class SanadDetector
{
    /**
     * @param  list<array<string, mixed>>  $defects
     * @return list<array<string, mixed>>
     */
    public function fromDefects(array $defects): array
    {
        return array_values(array_filter(
            $defects,
            static fn (array $defect): bool => str_starts_with((string) ($defect['type'] ?? ''), 'sanad_')
        ));
    }
}
