<?php

declare(strict_types=1);

namespace Mosab\QafyaDetector\Analysis\Rules;

/** Resolves collective/final وا after a stable dominant rawi. */
final class CollectiveWawAlefRule extends AbstractPoemLevelRule
{
    private const AUTHORITATIVE_DOMINANT_RATIO = 0.8;

    /**
     * @param  list<array<string, mixed>>  $endings
     * @return list<array<string, mixed>>
     */
    public function apply(array $endings): array
    {
        return $this->applyStableCollectiveWawAlefAfterDominantRawiOverride($endings);
    }

    /**
     * Resolve collective/final وا after the dominant rawi.
     *
     * Example:
     *   سوادُ / معادُ / قادوا / عادوا / زادوا
     *
     * Word-level may read قادوا as rawi=و, wasl=ا.
     * Poem context proves د is the committed rawi, while و is wasl and ا is khurooj.
     *
     * @param  list<array<string, mixed>>  $endings
     * @return list<array<string, mixed>>
     */
    private function applyStableCollectiveWawAlefAfterDominantRawiOverride(array $endings): array
    {
        /** @var array<string, int> $rawiCounts */
        $rawiCounts = [];

        $validCount = 0;

        foreach ($endings as $ending) {
            if (($ending['result']['status'] ?? null) !== 'ok') {
                continue;
            }

            $result = is_array($ending['result'] ?? null) ? $ending['result'] : [];
            $rawi = $this->componentLetter($result, 'rawi');

            if ($rawi === null || $rawi === '') {
                continue;
            }

            $validCount++;
            $rawiCounts[$rawi] = ($rawiCounts[$rawi] ?? 0) + 1;
        }

        if ($validCount < 3 || $rawiCounts === []) {
            return $endings;
        }

        arsort($rawiCounts);

        $dominantRawi = array_key_first($rawiCounts);

        if (! is_string($dominantRawi) || $dominantRawi === '') {
            return $endings;
        }

        $dominantRatio = $rawiCounts[$dominantRawi] / max(1, $validCount);

        if ($dominantRatio < self::AUTHORITATIVE_DOMINANT_RATIO) {
            return $endings;
        }

        foreach ($endings as $index => $ending) {
            if (($ending['result']['status'] ?? null) !== 'ok') {
                continue;
            }

            $word = $this->normalizedEndingWord($ending);

            if ($word === null || $word === '') {
                continue;
            }

            $chars = mb_str_split($word) ?: [];
            $length = count($chars);

            if ($length < 3) {
                continue;
            }

            $last = $chars[$length - 1] ?? null;
            $beforeLast = $chars[$length - 2] ?? null;
            $beforeWaw = $chars[$length - 3] ?? null;

            if ($last !== 'ا' || $beforeLast !== 'و' || $beforeWaw !== $dominantRawi) {
                continue;
            }

            $result = is_array($endings[$index]['result'] ?? null) ? $endings[$index]['result'] : [];

            if ($this->componentLetter($result, 'rawi') === $dominantRawi) {
                continue;
            }

            $endings[$index]['result'] = $this->forceCollectiveWawAlefAfterRawi($result, $dominantRawi);
        }

        return $endings;
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function forceCollectiveWawAlefAfterRawi(array $result, string $rawi): array
    {
        $word = $result['input']['normalized'] ?? null;
        $chars = is_string($word) ? (mb_str_split($word) ?: []) : [];

        $radf = null;

        if (count($chars) >= 4) {
            $rawiIndex = count($chars) - 3;
            $beforeRawi = $chars[$rawiIndex - 1] ?? null;

            if (in_array($beforeRawi, ['ا', 'آ', 'ى'], true)) {
                $radf = 'ا';
            } elseif (in_array($beforeRawi, ['و', 'ي'], true)) {
                $radf = $beforeRawi;
            }
        }

        $result['qafya']['category'] = 'mutlaqa';
        $result['qafya']['subtype'] = $radf !== null ? 'mutlaqa_mardoofa_madd' : 'mutlaqa_mujarrada';

        $result['qafya']['components']['taasis'] = null;
        $result['qafya']['components']['dakhiil'] = null;

        $result['qafya']['components']['radf'] = $radf === null ? null : [
            'letter' => $radf,
            'role' => 'radf',
            'family' => in_array($radf, ['ا', 'ى'], true) ? 'alif' : 'waw_yaa',
        ];

        $result['qafya']['components']['rawi'] = [
            'letter' => $rawi,
            'role' => 'rawi',
            'haraka' => null,
            'haraka_name' => null,
            'mujra' => null,
            'eligible' => true,
            'eligibility_reason' => 'collective_waw_alef_after_dominant_rawi',
        ];

        $result['qafya']['components']['wasl'] = [
            'letter' => 'و',
            'role' => 'wasl',
            'kind' => 'madd',
        ];

        $result['qafya']['components']['khurooj'] = [
            'letter' => 'ا',
            'role' => 'khurooj',
        ];

        $result['qafya']['motions']['mujra'] = null;
        $result['qafya']['motions']['tawjih'] = null;
        $result['qafya']['motions']['hadhw'] = $radf !== null ? 'vowel_before_radf' : null;
        $result['qafya']['motions']['ishbaa'] = null;
        $result['qafya']['motions']['nafadh'] = 'vowel_before_khurooj';

        $components = is_array($result['qafya']['components'] ?? null) ? $result['qafya']['components'] : [];
        $motions = is_array($result['qafya']['motions'] ?? null) ? $result['qafya']['motions'] : [];
        $signatures = $this->signatureBuilder->build($components, $motions);

        $surface = ($radf ?? '').$rawi.'وا';

        $result['signatures'] = $signatures;
        $result['pattern'] = [
            'surface' => $surface,
            'component' => $signatures['component'],
            'strict' => $signatures['strict'],
            'cluster' => $signatures['cluster'],
            'canonical' => $signatures['component'],
        ];
        $result['qafya']['pattern'] = $result['pattern'];

        if (isset($result['trace']) && is_array($result['trace'])) {
            $result['trace'][] = [
                'rule' => 'poem_level_collective_waw_alef_after_dominant_rawi',
                'decision' => 'dominant_rawi_kept_and_final_waw_alef_resolved_as_wasl_khurooj',
                'rawi' => $rawi,
                'radf' => $radf,
            ];
        }

        return $result;
    }
}
