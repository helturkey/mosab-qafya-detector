<?php

declare(strict_types=1);

namespace Mosab\QafyaDetector\Text;

/**
 * Splits Arabic poems into ordered hemistich/line strings.
 */
final class VerseSplitter
{
    /**
     * @param  string|array<int, string>  $poem
     * @return list<string>
     */
    public function split(string|array $poem): array
    {
        if (is_array($poem)) {
            return array_values(array_filter(array_map('trim', $poem), static fn (string $line): bool => $line !== ''));
        }

        $parts = preg_split('/(?:\r?\n|\*{3,}|\|)+/u', $poem, -1, PREG_SPLIT_NO_EMPTY);

        return array_values(array_filter(array_map('trim', $parts ?: []), static fn (string $line): bool => $line !== ''));
    }

    /**
     * @param  list<string>  $lines
     * @return list<array{sadr: string, ajz: string, index: int}>
     */
    public function pairHemistichs(array $lines): array
    {
        $pairs = [];
        for ($i = 0; $i + 1 < count($lines); $i += 2) {
            $pairs[] = ['index' => intdiv($i, 2), 'sadr' => $lines[$i], 'ajz' => $lines[$i + 1]];
        }

        return $pairs;
    }
}
