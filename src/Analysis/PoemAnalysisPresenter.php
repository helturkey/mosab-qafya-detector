<?php

declare(strict_types=1);

namespace Mosab\QafyaDetector\Analysis;

/** Converts normalized detection arrays into public response fragments. */
final class PoemAnalysisPresenter
{
    /** @param list<array<string, mixed>> $endings */
    public function averageConfidence(array $endings): float
    {
        if ($endings === []) {
            return 0.0;
        }
        $sum = 0.0;
        foreach ($endings as $ending) {
            $sum += (float) ($ending['result']['confidence']['score'] ?? 0.0);
        }

        return round($sum / count($endings), 4);
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    public function dominantPayload(array $result): array
    {
        return [
            'rawi' => $this->componentLetter($result, 'rawi'),
            'rawi_haraka' => $result['qafya']['components']['rawi']['haraka'] ?? null,
            'mujra' => $result['qafya']['motions']['mujra'] ?? null,
            'radf' => $this->componentLetter($result, 'radf'),
            'taasis' => $this->componentLetter($result, 'taasis'),
            'dakhiil' => $this->componentLetter($result, 'dakhiil'),
            'wasl' => $this->componentLetter($result, 'wasl'),
            'khurooj' => $this->componentLetter($result, 'khurooj'),
            'segment' => $result['segment']['surface'] ?? null,
            'pattern' => $result['pattern']['surface'] ?? null,
            'signature' => $result['signatures']['strict'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $mostFrequentResult
     * @param  array{signature: ?string, count: int, ratio: float}  $dominant
     * @return array<string, mixed>
     */
    public function indexing(array $mostFrequentResult, array $dominant, float $dominantRatio, bool $dominantIsAuthoritative): array
    {
        return [
            'most_frequent_rawi' => $this->componentLetter($mostFrequentResult, 'rawi'),
            'most_frequent_rawi_haraka' => $mostFrequentResult['qafya']['components']['rawi']['haraka'] ?? null,
            'most_frequent_signature' => $dominant['signature'],
            'most_frequent_strict_signature' => $mostFrequentResult['signatures']['strict'] ?? null,
            'most_frequent_segment' => $mostFrequentResult['segment']['surface'] ?? null,
            'most_frequent_pattern' => $mostFrequentResult['pattern']['surface'] ?? null,
            'most_frequent_count' => $dominant['count'],
            'most_frequent_ratio' => $dominantRatio,
            'dominant_is_authoritative' => $dominantIsAuthoritative,
            'usable_for_filtering' => $dominantIsAuthoritative,
        ];
    }

    /** @param array<string, mixed> $result */
    private function componentLetter(array $result, string $key): ?string
    {
        $value = $result['qafya']['components'][$key]['letter'] ?? null;

        return is_string($value) ? $value : null;
    }
}
