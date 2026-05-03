<?php

declare(strict_types=1);

namespace Mosab\QafyaDetector\Analysis;

use Mosab\QafyaDetector\Classification\QafyaSignatureBuilder;
use Mosab\QafyaDetector\Support\ArabicText;

/**
 * Builds the public dominant result for a poem-level rawi cluster.
 *
 * This class intentionally separates canonical identity from visible pattern:
 * hamza seats may cluster as ء while still displaying ئا, and unstable
 * taasis/dakhiil/radf values are kept in endings instead of polluting the
 * poem-level dominant payload.
 */
final class PublicDominantQafyaNormalizer
{
    public function __construct(
        private readonly QafyaSignatureBuilder $signatureBuilder = new QafyaSignatureBuilder,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $endings
     * @return array<string, mixed>|null
     */
    public function normalize(array $endings, ?string $signature): ?array
    {
        return $this->publicResultForRawiCluster($endings, $signature);
    }

    /**
     * @param  list<array<string, mixed>>  $endings
     * @return array<string, mixed>|null
     */
    private function resultForRawiCluster(array $endings, ?string $signature): ?array
    {
        if ($signature === null) {
            return null;
        }

        foreach ($endings as $ending) {
            $result = is_array($ending['result'] ?? null) ? $ending['result'] : [];
            if ($this->rawiClusterSignature($result) === $signature) {
                return $result;
            }
        }

        return null;
    }

    /**
     * Build the public poem-level result for a rawi cluster.
     *
     * The rawi cluster may contain many valid component variants:
     * - تعاقبي => taasis=ا, dakhiil=ق, rawi=ب, wasl=ي
     * - مثالبي => taasis=ا, dakhiil=ل, rawi=ب, wasl=ي
     * - مشاغبِ => rawi=ب, no written wasl
     *
     * Public dominant output must not borrow unstable taasis/dakhiil/radf
     * from the first ending in the cluster. Those remain available in endings.
     *
     * @param  list<array<string, mixed>>  $endings
     * @return array<string, mixed>|null
     */
    private function publicResultForRawiCluster(array $endings, ?string $signature): ?array
    {
        $representative = $this->resultForRawiCluster($endings, $signature);

        if ($representative === null || $signature === null) {
            return $representative;
        }

        $clusterResults = $this->resultsForRawiCluster($endings, $signature);

        if (count($clusterResults) < 2) {
            return $representative;
        }

        return $this->normalizePublicClusterResult($representative, $clusterResults);
    }

    /**
     * @param  list<array<string, mixed>>  $endings
     * @return list<array<string, mixed>>
     */
    private function resultsForRawiCluster(array $endings, ?string $signature): array
    {
        if ($signature === null) {
            return [];
        }

        $results = [];

        foreach ($endings as $ending) {
            $result = is_array($ending['result'] ?? null) ? $ending['result'] : [];

            if (($result['status'] ?? null) !== 'ok') {
                continue;
            }

            if ($this->rawiClusterSignature($result) !== $signature) {
                continue;
            }

            $results[] = $result;
        }

        return $results;
    }

    /**
     * Normalize only the public dominant/indexing result.
     *
     * This does not mutate per-ending details. It only prevents the public
     * dominant payload from exposing accidental components copied from one
     * representative ending.
     *
     * @param  array<string, mixed>  $representative
     * @param  list<array<string, mixed>>  $clusterResults
     * @return array<string, mixed>
     */
    private function normalizePublicClusterResult(array $representative, array $clusterResults): array
    {
        $rawi = $this->componentLetter($representative, 'rawi');

        if ($rawi === null || $rawi === '') {
            return $representative;
        }

        $rawi = ArabicText::canonicalRawiIdentity($rawi) ?? $rawi;
        $rawiSurface = $this->stableRawiSurfaceLetter($clusterResults, $rawi) ?? $rawi;

        $radf = $this->stableComponentLetter($clusterResults, 'radf', 0.80);
        $wasl = $this->stableComponentLetter($clusterResults, 'wasl', 0.60);
        $khurooj = $this->stableComponentLetter($clusterResults, 'khurooj', 0.80);

        [$taasis, $dakhiil] = $this->stableTaasisDakhiilPair($clusterResults, 0.80);

        $rawiHaraka = $this->stableNestedString($clusterResults, ['qafya', 'components', 'rawi', 'haraka'], 0.80);
        $rawiHarakaName = $this->stableNestedString($clusterResults, ['qafya', 'components', 'rawi', 'haraka_name'], 0.80);
        $mujra = $this->stableNestedString($clusterResults, ['qafya', 'motions', 'mujra'], 0.80);

        $result = $representative;

        $rawiComponent = is_array($result['qafya']['components']['rawi'] ?? null)
            ? $result['qafya']['components']['rawi']
            : [];

        $rawiComponent['letter'] = $rawi;
        $rawiComponent['role'] = 'rawi';
        $rawiComponent['surface_letter'] = $rawiSurface !== $rawi ? $rawiSurface : null;
        $rawiComponent['haraka'] = $rawiHaraka;
        $rawiComponent['haraka_name'] = $rawiHarakaName;
        $rawiComponent['mujra'] = $mujra;
        $rawiComponent['eligible'] = true;
        $rawiComponent['eligibility_reason'] = $rawiComponent['eligibility_reason'] ?? 'poem_level_public_rawi_cluster';

        $result['qafya']['components']['taasis'] = $this->publicComponent($taasis, 'taasis');
        $result['qafya']['components']['dakhiil'] = $this->publicComponent($dakhiil, 'dakhiil');
        $result['qafya']['components']['radf'] = $this->publicComponent($radf, 'radf', [
            'family' => $this->publicRadfFamily($radf),
        ]);
        $result['qafya']['components']['rawi'] = $rawiComponent;
        $result['qafya']['components']['wasl'] = $this->publicComponent($wasl, 'wasl', [
            'kind' => $wasl === 'ه' ? 'haa' : 'madd',
        ]);
        $result['qafya']['components']['khurooj'] = $this->publicComponent($khurooj, 'khurooj');

        $result['qafya']['category'] = $wasl !== null || $khurooj !== null
            ? 'mutlaqa'
            : 'muqayyada';

        $result['qafya']['subtype'] = match (true) {
            $wasl !== null && $radf !== null => 'mutlaqa_mardoofa_madd',
            $wasl !== null => 'mutlaqa_mujarrada',
            $radf !== null => 'muqayyada_mardoofa',
            default => 'muqayyada_mujarrada',
        };

        $result['qafya']['motions']['rass'] = $taasis !== null ? 'required_before_taasis' : null;
        $result['qafya']['motions']['ishbaa'] = $dakhiil !== null ? ($result['qafya']['motions']['ishbaa'] ?? null) : null;
        $result['qafya']['motions']['hadhw'] = $radf !== null ? 'vowel_before_radf' : null;
        $result['qafya']['motions']['tawjih'] = $wasl === null ? $mujra : null;
        $result['qafya']['motions']['mujra'] = $mujra;
        $result['qafya']['motions']['nafadh'] = $khurooj !== null ? 'vowel_before_khurooj' : null;

        $components = is_array($result['qafya']['components'] ?? null) ? $result['qafya']['components'] : [];
        $motions = is_array($result['qafya']['motions'] ?? null) ? $result['qafya']['motions'] : [];

        $signatures = $this->signatureBuilder->build($components, $motions);
        $surface = $this->publicPatternSurface($components);

        $result['signatures'] = $signatures;
        $result['signatures']['visual'] = $surface;

        $result['pattern'] = [
            'surface' => $surface,
            'component' => $signatures['component'],
            'strict' => $signatures['strict'],
            'cluster' => $signatures['cluster'],
            'canonical' => $signatures['component'],
        ];

        $result['qafya']['pattern'] = $result['pattern'];

        $result['segment'] = [
            'surface' => $surface,
            'arudic' => $surface,
            'method' => 'poem_level_public_pattern',
            'complete' => true,
            'moving_count_between_sakins' => null,
            'last_sakin_index' => null,
            'previous_sakin_index' => null,
            'start_index' => null,
            'warnings' => [],
        ];

        $result['qafya']['segment'] = $result['segment'];
        $result['qafya']['boundary'] = $result['segment'];

        if (isset($result['trace']) && is_array($result['trace'])) {
            $result['trace'][] = [
                'rule' => 'poem_level_public_cluster_normalization',
                'decision' => 'unstable_components_removed_from_public_dominant_payload',
                'rawi' => $rawi,
                'radf' => $radf,
                'taasis' => $taasis,
                'dakhiil' => $dakhiil,
                'wasl' => $wasl,
                'khurooj' => $khurooj,
            ];
        }

        return $result;
    }

    /**
     * @param  list<array<string, mixed>>  $results
     */
    private function stableComponentLetter(array $results, string $component, float $threshold): ?string
    {
        $total = count($results);

        if ($total === 0) {
            return null;
        }

        /** @var array<string, int> $counts */
        $counts = [];

        foreach ($results as $result) {
            $letter = $this->componentLetter($result, $component);

            if ($letter === null || $letter === '') {
                continue;
            }

            $counts[$letter] = ($counts[$letter] ?? 0) + 1;
        }

        if ($counts === []) {
            return null;
        }

        arsort($counts);

        $letter = array_key_first($counts);
        $count = $letter !== null ? $counts[$letter] : 0;

        return $letter !== null && ($count / max(1, $total)) >= $threshold
            ? $letter
            : null;
    }

    /**
     * Taasis and dakhiil must be stable as a pair.
     *
     * Stable taasis alone is not enough, because exposing:
     * Tا-Dق-Rب-Wي
     * from تعاقبي makes the whole poem look like its qafya is اقبي,
     * while the actual public qafya identity is بي.
     *
     * @param  list<array<string, mixed>>  $results
     * @return array{0: ?string, 1: ?string}
     */
    private function stableTaasisDakhiilPair(array $results, float $threshold): array
    {
        $total = count($results);

        if ($total === 0) {
            return [null, null];
        }

        /** @var array<string, array{count: int, taasis: string, dakhiil: string}> $pairs */
        $pairs = [];

        foreach ($results as $result) {
            $taasis = $this->componentLetter($result, 'taasis');
            $dakhiil = $this->componentLetter($result, 'dakhiil');

            if ($taasis === null || $taasis === '' || $dakhiil === null || $dakhiil === '') {
                continue;
            }

            $key = $taasis.'|'.$dakhiil;

            if (! isset($pairs[$key])) {
                $pairs[$key] = [
                    'count' => 0,
                    'taasis' => $taasis,
                    'dakhiil' => $dakhiil,
                ];
            }

            $pairs[$key]['count']++;
        }

        if ($pairs === []) {
            return [null, null];
        }

        uasort(
            $pairs,
            static fn (array $a, array $b): int => $b['count'] <=> $a['count'],
        );

        $pair = reset($pairs);

        if (! is_array($pair) || ($pair['count'] / max(1, $total)) < $threshold) {
            return [null, null];
        }

        return [$pair['taasis'], $pair['dakhiil']];
    }

    /**
     * @param  list<array<string, mixed>>  $results
     * @param  list<string>  $path
     */
    private function stableNestedString(array $results, array $path, float $threshold): ?string
    {
        $total = count($results);

        if ($total === 0) {
            return null;
        }

        /** @var array<string, int> $counts */
        $counts = [];

        foreach ($results as $result) {
            $value = $result;

            foreach ($path as $key) {
                if (! is_array($value) || ! array_key_exists($key, $value)) {
                    $value = null;
                    break;
                }

                $value = $value[$key];
            }

            if (! is_string($value) || $value === '') {
                continue;
            }

            $counts[$value] = ($counts[$value] ?? 0) + 1;
        }

        if ($counts === []) {
            return null;
        }

        arsort($counts);

        $value = array_key_first($counts);
        $count = $value !== null ? $counts[$value] : 0;

        return $value !== null && ($count / max(1, $total)) >= $threshold
            ? $value
            : null;
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>|null
     */
    private function publicComponent(?string $letter, string $role, array $extra = []): ?array
    {
        if ($letter === null || $letter === '') {
            return null;
        }

        return [
            'letter' => $letter,
            'role' => $role,
        ] + $extra;
    }

    private function publicRadfFamily(?string $letter): ?string
    {
        return match ($letter) {
            'ا', 'ى' => 'alif',
            'و', 'ي' => 'waw_yaa',
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $components
     */
    private function publicPatternSurface(array $components): string
    {
        $letters = [];

        $taasis = $this->componentSurfaceLetterFromComponents($components, 'taasis');
        $dakhiil = $this->componentSurfaceLetterFromComponents($components, 'dakhiil');

        if ($taasis !== null && $dakhiil !== null) {
            $letters[] = $taasis;
            $letters[] = $dakhiil;
        }

        foreach (['radf', 'rawi', 'wasl', 'khurooj'] as $component) {
            $letter = $this->componentSurfaceLetterFromComponents($components, $component);

            if ($letter !== null) {
                $letters[] = $letter;
            }
        }

        return implode('', $letters);
    }

    /**
     * @param  array<string, mixed>  $components
     */
    private function componentSurfaceLetterFromComponents(array $components, string $key): ?string
    {
        $component = $components[$key] ?? null;

        if (! is_array($component)) {
            return null;
        }

        $surface = $component['surface_letter'] ?? null;

        if (is_string($surface) && $surface !== '') {
            return $surface;
        }

        $letter = $component['letter'] ?? null;

        return is_string($letter) && $letter !== '' ? $letter : null;
    }

    /** @param array<string, mixed> $result */
    private function rawiClusterSignature(array $result): ?string
    {
        $rawi = $this->componentLetter($result, 'rawi');
        $rawi = ArabicText::canonicalRawiIdentity($rawi);

        if ($rawi === null || $rawi === '') {
            return null;
        }

        return 'R'.$rawi;
    }

    /** @param array<string, mixed> $result */
    private function componentLetter(array $result, string $key): ?string
    {
        $value = $result['qafya']['components'][$key]['letter'] ?? null;

        return is_string($value) ? $value : null;
    }

    /**
     * Keep canonical rawi identity for clustering/signature, but preserve the
     * dominant visible hamza seat for public pattern/segment.
     *
     * Example:
     * - rawi identity: ء
     * - visible rawi: ئ
     * - visible pattern: ئا
     *
     * @param  list<array<string, mixed>>  $results
     */
    private function stableRawiSurfaceLetter(array $results, string $canonicalRawi, float $threshold = 0.60): ?string
    {
        /** @var array<string, int> $counts */
        $counts = [];
        $eligible = 0;

        foreach ($results as $result) {
            $surface = $this->visibleRawiLetterFromResult($result, $canonicalRawi);

            if ($surface === null || $surface === '') {
                continue;
            }

            $eligible++;
            $counts[$surface] = ($counts[$surface] ?? 0) + 1;
        }

        if ($eligible === 0 || $counts === []) {
            return null;
        }

        arsort($counts);

        $surface = array_key_first($counts);
        $count = $surface !== null ? $counts[$surface] : 0;

        return $surface !== null && ($count / max(1, $eligible)) >= $threshold
            ? $surface
            : null;
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function visibleRawiLetterFromResult(array $result, string $canonicalRawi): ?string
    {
        $rawiComponent = $result['qafya']['components']['rawi'] ?? null;

        if (is_array($rawiComponent)) {
            $surface = $rawiComponent['surface_letter'] ?? null;

            if (
                is_string($surface)
                && $surface !== ''
                && (ArabicText::canonicalRawiIdentity($surface) ?? $surface) === $canonicalRawi
            ) {
                return $surface;
            }
        }

        /*
         * Prefer the old visible pattern when it contains a non-canonical hamza
         * seat. This protects cases such as ئا from becoming ءا.
         */
        $pattern = $result['pattern']['surface'] ?? null;

        if (is_string($pattern) && $pattern !== '') {
            foreach (mb_str_split($pattern) ?: [] as $char) {
                if (
                    $char !== $canonicalRawi
                    && (ArabicText::canonicalRawiIdentity($char) ?? $char) === $canonicalRawi
                ) {
                    return $char;
                }
            }
        }

        if (is_array($rawiComponent)) {
            $letter = $rawiComponent['letter'] ?? null;

            if (
                is_string($letter)
                && $letter !== ''
                && (ArabicText::canonicalRawiIdentity($letter) ?? $letter) === $canonicalRawi
            ) {
                return $letter;
            }
        }

        return null;
    }
}
