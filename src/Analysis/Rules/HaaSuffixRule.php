<?php

declare(strict_types=1);

namespace Mosab\QafyaDetector\Analysis\Rules;

use Mosab\QafyaDetector\Support\ArabicText;

/** Resolves stable ـها and ـيه suffix families at poem level. */
final class HaaSuffixRule extends AbstractPoemLevelRule
{
    /**
     * @param  list<array<string, mixed>>  $endings
     * @return list<array<string, mixed>>
     */
    public function apply(array $endings): array
    {
        $endings = $this->applyStableHaaAlefPronounSuffixOverride($endings);
        $endings = $this->applyStableYaaHaaSuffixOverride($endings);

        return $endings;
    }

    /**
     * Resolve stable ـها suffix when poem context proves that ها is a pronoun suffix.
     *
     * Examples:
     * - ديجورُها / يعفورُها / مساميرُها => rawi=ر, wasl=ه, khurooj=ا
     * - أنساعَها / بقاعَها / يراعَها    => rawi=ع, radf=ا, wasl=ه, khurooj=ا
     *
     * This is poem-level only. It does not make every isolated word ending in ها
     * resolve to the previous consonant.
     *
     * @param  list<array<string, mixed>>  $endings
     * @return list<array<string, mixed>>
     */
    private function applyStableHaaAlefPronounSuffixOverride(array $endings): array
    {
        $validCount = 0;

        /** @var array<int, array{rawi_surface: string, rawi_identity: string, radf: string|null}> $eligibleByIndex */
        $eligibleByIndex = [];

        /** @var array<string, int> $preHaaCounts */
        $preHaaCounts = [];

        foreach ($endings as $index => $ending) {
            if (($ending['result']['status'] ?? null) !== 'ok') {
                continue;
            }

            $validCount++;

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
            $haa = $chars[$length - 2] ?? null;
            $preHaa = $chars[$length - 3] ?? null;

            if (! in_array($last, ['ا', 'ى'], true) || $haa !== 'ه' || ! is_string($preHaa) || $preHaa === '') {
                continue;
            }

            /*
             * Do not rewrite اها / آها as pronoun suffix.
             *
             * In endings like رآها / غناها / نساها:
             *   rawi   = ه
             *   radf   = ا
             *   wasl   = ا
             *   pattern = اها
             *
             * The pre-haa alef is not the rawi; it is radf before haa.
             */
            if (in_array($preHaa, ['ا', 'آ', 'ى'], true)) {
                continue;
            }

            $rawiIdentity = ArabicText::canonicalRawiIdentity($preHaa) ?? $preHaa;
            $radf = $this->stableSuffixRadfBeforeRawi($chars, $length - 3);

            $eligibleByIndex[$index] = [
                'rawi_surface' => $preHaa,
                'rawi_identity' => $rawiIdentity,
                'radf' => $radf,
            ];

            $preHaaCounts[$rawiIdentity] = ($preHaaCounts[$rawiIdentity] ?? 0) + 1;
        }

        if ($validCount < 3 || $eligibleByIndex === []) {
            return $endings;
        }

        $suffixRatio = count($eligibleByIndex) / max(1, $validCount);

        if ($suffixRatio < 0.75) {
            return $endings;
        }

        arsort($preHaaCounts);

        $dominantRawi = array_key_first($preHaaCounts);

        if (! is_string($dominantRawi) || $dominantRawi === '') {
            return $endings;
        }

        $dominantRatio = $preHaaCounts[$dominantRawi] / max(1, count($eligibleByIndex));

        /*
         * Do not rewrite جمالها / ديارها / خصالها where the pre-haa rawi is not stable.
         * Stable pre-haa means ها is probably a suffix after the committed rawi.
         */
        if ($dominantRatio < 0.8) {
            return $endings;
        }

        $stableRadf = $this->stableRadfForSuffixOverride($eligibleByIndex, $dominantRawi);

        foreach ($eligibleByIndex as $index => $data) {
            if ($data['rawi_identity'] !== $dominantRawi) {
                continue;
            }

            $result = is_array($endings[$index]['result'] ?? null) ? $endings[$index]['result'] : [];

            $endings[$index]['result'] = $this->forceHaaAlefPronounSuffixAfterRawi(
                result: $result,
                rawi: $dominantRawi,
                rawiSurface: $data['rawi_surface'],
                preservedRadf: $stableRadf,
            );
        }

        return $endings;
    }

    /**
     * Resolve stable ـيه suffix at poem level.
     *
     * Example:
     * إليهِ / أبيهِ / يديهِ / ثغريهِ / نابيهِ...
     *
     * Word-level may read these as pre-yaa rawi + ي radf + ه wasl.
     * Across a poem, high repetition of ـيه with diverse pre-yaa letters proves
     * the public qafya family is rawi=ي, wasl=ه.
     *
     * @param  list<array<string, mixed>>  $endings
     * @return list<array<string, mixed>>
     */
    private function applyStableYaaHaaSuffixOverride(array $endings): array
    {
        $validCount = 0;

        /** @var list<int> $eligibleIndexes */
        $eligibleIndexes = [];

        /** @var array<string, int> $preYaaCounts */
        $preYaaCounts = [];

        foreach ($endings as $index => $ending) {
            if (($ending['result']['status'] ?? null) !== 'ok') {
                continue;
            }

            $validCount++;

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
            $yaa = $chars[$length - 2] ?? null;
            $preYaa = $chars[$length - 3] ?? null;

            if ($last !== 'ه' || $yaa !== 'ي' || ! is_string($preYaa) || $preYaa === '') {
                continue;
            }

            $eligibleIndexes[] = $index;
            $preYaaCounts[$preYaa] = ($preYaaCounts[$preYaa] ?? 0) + 1;
        }

        if ($validCount < 3 || $eligibleIndexes === []) {
            return $endings;
        }

        $suffixRatio = count($eligibleIndexes) / max(1, $validCount);

        if ($suffixRatio < 0.75) {
            return $endings;
        }

        /*
         * If pre-yaa letters vary, the existing pre-yaa rawi reading will split
         * the poem into many false rawis. This is exactly the ـيه poem family.
         */
        $preYaaDiversity = count($preYaaCounts);

        if ($preYaaDiversity < 2 && count($eligibleIndexes) < $validCount) {
            return $endings;
        }

        foreach ($eligibleIndexes as $index) {
            $result = is_array($endings[$index]['result'] ?? null) ? $endings[$index]['result'] : [];
            $endings[$index]['result'] = $this->forceYaaHaaSuffixRawi($result);
        }

        return $endings;
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function forceHaaAlefPronounSuffixAfterRawi(
        array $result,
        string $rawi,
        string $rawiSurface,
        ?string $preservedRadf,
    ): array {
        $rawi = ArabicText::canonicalRawiIdentity($rawi) ?? $rawi;
        $rawiSurface = $rawiSurface !== '' ? $rawiSurface : $rawi;

        $result['qafya']['category'] = 'mutlaqa';
        $result['qafya']['subtype'] = $preservedRadf !== null
            ? 'mutlaqa_mardoofa_haa'
            : 'mutlaqa_mujarrada';

        $result['qafya']['components']['taasis'] = null;
        $result['qafya']['components']['dakhiil'] = null;

        $result['qafya']['components']['radf'] = $preservedRadf === null ? null : [
            'letter' => $preservedRadf,
            'role' => 'radf',
            'family' => in_array($preservedRadf, ['ا', 'ى'], true) ? 'alif' : 'waw_yaa',
        ];

        $result['qafya']['components']['rawi'] = [
            'letter' => $rawi,
            'role' => 'rawi',
            'surface_letter' => $rawiSurface !== $rawi ? $rawiSurface : null,
            'haraka' => null,
            'haraka_name' => null,
            'mujra' => null,
            'eligible' => true,
            'eligibility_reason' => 'stable_haa_alef_pronoun_suffix_after_dominant_rawi',
        ];

        $result['qafya']['components']['wasl'] = [
            'letter' => 'ه',
            'role' => 'wasl',
            'kind' => 'haa',
        ];

        $result['qafya']['components']['khurooj'] = [
            'letter' => 'ا',
            'role' => 'khurooj',
        ];

        $result['qafya']['motions']['mujra'] = null;
        $result['qafya']['motions']['tawjih'] = null;
        $result['qafya']['motions']['hadhw'] = $preservedRadf !== null ? 'vowel_before_radf' : null;
        $result['qafya']['motions']['ishbaa'] = null;
        $result['qafya']['motions']['nafadh'] = 'vowel_before_khurooj';

        $components = is_array($result['qafya']['components'] ?? null) ? $result['qafya']['components'] : [];
        $motions = is_array($result['qafya']['motions'] ?? null) ? $result['qafya']['motions'] : [];
        $signatures = $this->signatureBuilder->build($components, $motions);

        $surface = ($preservedRadf ?? '').$rawiSurface.'ها';

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
                'rule' => 'poem_level_stable_haa_alef_pronoun_suffix',
                'decision' => 'pre_haa_letter_is_rawi_haa_wasl_alef_khurooj',
                'rawi' => $rawi,
                'rawi_surface' => $rawiSurface,
                'preserved_radf' => $preservedRadf,
            ];
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function forceYaaHaaSuffixRawi(array $result): array
    {
        $result['qafya']['category'] = 'mutlaqa';
        $result['qafya']['subtype'] = 'mutlaqa_mujarrada';

        $result['qafya']['components']['taasis'] = null;
        $result['qafya']['components']['dakhiil'] = null;
        $result['qafya']['components']['radf'] = null;

        $result['qafya']['components']['rawi'] = [
            'letter' => 'ي',
            'role' => 'rawi',
            'haraka' => null,
            'haraka_name' => null,
            'mujra' => null,
            'eligible' => true,
            'eligibility_reason' => 'stable_yaa_haa_suffix_poem_family',
        ];

        $result['qafya']['components']['wasl'] = [
            'letter' => 'ه',
            'role' => 'wasl',
            'kind' => 'haa',
        ];

        $result['qafya']['components']['khurooj'] = null;

        $result['qafya']['motions']['mujra'] = null;
        $result['qafya']['motions']['tawjih'] = null;
        $result['qafya']['motions']['hadhw'] = null;
        $result['qafya']['motions']['ishbaa'] = null;
        $result['qafya']['motions']['nafadh'] = null;

        $components = is_array($result['qafya']['components'] ?? null) ? $result['qafya']['components'] : [];
        $motions = is_array($result['qafya']['motions'] ?? null) ? $result['qafya']['motions'] : [];
        $signatures = $this->signatureBuilder->build($components, $motions);

        $result['signatures'] = $signatures;
        $result['pattern'] = [
            'surface' => 'يه',
            'component' => $signatures['component'],
            'strict' => $signatures['strict'],
            'cluster' => $signatures['cluster'],
            'canonical' => $signatures['component'],
        ];
        $result['qafya']['pattern'] = $result['pattern'];

        if (isset($result['trace']) && is_array($result['trace'])) {
            $result['trace'][] = [
                'rule' => 'poem_level_stable_yaa_haa_suffix',
                'decision' => 'yaa_is_rawi_and_final_haa_is_wasl',
            ];
        }

        return $result;
    }

    /**
     * @param  list<string>  $chars
     */
    private function stableSuffixRadfBeforeRawi(array $chars, int $rawiIndex): ?string
    {
        $beforeRawi = $chars[$rawiIndex - 1] ?? null;

        if (! is_string($beforeRawi)) {
            return null;
        }

        return match ($beforeRawi) {
            'ا', 'آ', 'ى' => 'ا',
            'و', 'ي' => $beforeRawi,
            default => null,
        };
    }

    /**
     * @param  array<int, array{rawi_surface: string, rawi_identity: string, radf: string|null}>  $eligibleByIndex
     */
    private function stableRadfForSuffixOverride(array $eligibleByIndex, string $dominantRawi): ?string
    {
        /** @var array<string, int> $radfCounts */
        $radfCounts = [];
        $eligibleCount = 0;

        foreach ($eligibleByIndex as $data) {
            if ($data['rawi_identity'] !== $dominantRawi) {
                continue;
            }

            $eligibleCount++;

            if ($data['radf'] !== null) {
                $radfCounts[$data['radf']] = ($radfCounts[$data['radf']] ?? 0) + 1;
            }
        }

        if ($eligibleCount === 0 || $radfCounts === []) {
            return null;
        }

        arsort($radfCounts);

        $dominantRadf = array_key_first($radfCounts);

        if (! is_string($dominantRadf)) {
            return null;
        }

        return ($radfCounts[$dominantRadf] / max(1, $eligibleCount)) >= 0.8
            ? $dominantRadf
            : null;
    }
}
