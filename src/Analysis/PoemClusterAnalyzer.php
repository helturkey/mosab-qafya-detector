<?php

declare(strict_types=1);

namespace Mosab\QafyaDetector\Analysis;

use Mosab\QafyaDetector\Support\ArabicText;

/** Groups poem endings by public rawi identity and reports consistency notes. */
final class PoemClusterAnalyzer
{
    private const AUTHORITATIVE_DOMINANT_RATIO = 0.8;

    /**
     * @param  list<array<string, mixed>>  $endings
     * @return array<string, mixed>|null
     */
    public function firstValid(array $endings): ?array
    {
        foreach ($endings as $ending) {
            if (($ending['result']['status'] ?? null) === 'ok') {
                return $ending;
            }
        }

        return null;
    }

    /**
     * Public poem-level clusters are grouped by rawi only.
     *
     * Do not include radf, wasl, khurooj, taasis, dakhiil, or pattern in this
     * identity. Those belong to detailed signatures, defects, and sanad. This
     * prevents valid poems such as جديدا / يعودا / جيدا / جودا from being split
     * into two qafyas merely because the radf alternates between ي and و.
     *
     * @param  list<array<string, mixed>>  $endings
     * @return list<array{signature: ?string, count: int, ratio: float}>
     */
    public function clusters(array $endings): array
    {
        /** @var array<string, int> $map */
        $map = [];

        foreach ($endings as $ending) {
            $result = is_array($ending['result'] ?? null) ? $ending['result'] : [];
            $signature = $this->rawiClusterSignature($result) ?? 'unknown';
            $map[$signature] = ($map[$signature] ?? 0) + 1;
        }

        arsort($map);

        $total = max(1, count($endings));
        $clusters = [];

        foreach ($map as $signature => $count) {
            $clusters[] = [
                'signature' => $signature === 'unknown' ? null : $signature,
                'count' => $count,
                'ratio' => round($count / $total, 4),
            ];
        }

        return $clusters;
    }

    /** @param array<string, mixed> $result */
    public function rawiClusterSignature(array $result): ?string
    {
        $rawi = $this->componentLetter($result, 'rawi');
        $rawi = ArabicText::canonicalRawiIdentity($rawi);

        if ($rawi === null || $rawi === '') {
            return null;
        }

        return 'R'.$rawi;
    }

    /**
     * Only defects that break the public qafya identity should force review.
     *
     * Component/surface variants such as wasl, khurooj, radf, or haraka changes
     * can remain visible in the defects array without turning an otherwise
     * single-rawi poem into review.
     *
     * @param  list<array<string, mixed>>  $defects
     */
    public function hasBlockingDefects(array $defects): bool
    {
        foreach ($defects as $defect) {
            $type = is_string($defect['type'] ?? null) ? $defect['type'] : null;

            if (in_array($type, [
                'rawi_mismatch',
                'missing_rawi',
                'invalid_reference',
                'unresolved_reference',
                'no_valid_qafya_reference',
            ], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<array<string, mixed>>  $endings
     * @param  list<array{signature: ?string, count: int, ratio: float}>  $clusters
     * @return list<string>
     */
    public function notes(array $endings, array $clusters, float $dominantRatio, bool $dominantIsAuthoritative): array
    {
        /** @var list<string> $notes */
        $notes = [];

        foreach ($endings as $ending) {
            if (($ending['result']['segment']['complete'] ?? false) === false) {
                $notes[] = 'some_endings_used_estimated_boundary_because_diacritics_are_missing';
                break;
            }
        }

        if (count($clusters) > 1) {
            $notes[] = 'multiple_qafya_clusters_detected';

            if ($dominantIsAuthoritative) {
                $notes[] = 'dominant_cluster_is_authoritative_but_secondary_clusters_exist';
            }
        }

        if (! $dominantIsAuthoritative && $clusters !== []) {
            $notes[] = 'dominant_cluster_is_not_authoritative_use_clusters_or_endings';
        }

        if ($dominantRatio > 0.0 && $dominantRatio < self::AUTHORITATIVE_DOMINANT_RATIO) {
            $notes[] = 'dominant_ratio_below_authoritative_threshold';
        }

        if ($this->hasDominantTie($clusters)) {
            $notes[] = 'dominant_cluster_tie_detected';
        }

        return array_values(array_unique($notes));
    }

    /** @param list<array{signature: ?string, count: int, ratio: float}> $clusters */
    private function hasDominantTie(array $clusters): bool
    {
        if (count($clusters) < 2) {
            return false;
        }

        return $clusters[0]['count'] === $clusters[1]['count'];
    }

    /** @param array<string, mixed> $result */
    private function componentLetter(array $result, string $key): ?string
    {
        $value = $result['qafya']['components'][$key]['letter'] ?? null;

        return is_string($value) ? $value : null;
    }
}
