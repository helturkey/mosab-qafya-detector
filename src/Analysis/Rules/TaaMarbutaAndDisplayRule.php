<?php

declare(strict_types=1);

namespace Mosab\QafyaDetector\Analysis\Rules;

/** Resolves contextual ة/ت families and final ى display-only repair. */
final class TaaMarbutaAndDisplayRule extends AbstractPoemLevelRule
{
    /**
     * @param  list<array<string, mixed>>  $endings
     * @return list<array<string, mixed>>
     */
    public function apply(array $endings): array
    {
        $endings = $this->applyTaaMarbutaAndKasraYaaContextOverride($endings);
        $endings = $this->applyVisibleFinalMaqsuraPatternForSingleEnding($endings);

        return $endings;
    }

    /**
     * Resolve final ة as ت when poem context proves the taa family,
     * and absorb final ي as kasra-ishbaa when it rhymes with the same ت family.
     *
     * @param  list<array<string, mixed>>  $endings
     * @return list<array<string, mixed>>
     */
    private function applyTaaMarbutaAndKasraYaaContextOverride(array $endings): array
    {
        $taaMarbutaIndexes = [];
        $explicitTaaIndexes = [];
        $explicitHaaIndexes = [];

        foreach ($endings as $index => $ending) {
            if (($ending['result']['status'] ?? null) !== 'ok') {
                continue;
            }

            $word = $this->endingWord($ending);

            if ($word !== null && $this->endsWithTaaMarbuta($word)) {
                $taaMarbutaIndexes[] = $index;

                continue;
            }

            $result = is_array($ending['result'] ?? null) ? $ending['result'] : [];
            $rawi = $this->componentLetter($result, 'rawi');

            if ($rawi === 'ت') {
                $explicitTaaIndexes[] = $index;
            } elseif ($rawi === 'ه') {
                $explicitHaaIndexes[] = $index;
            }
        }

        if ($taaMarbutaIndexes !== [] && $explicitTaaIndexes !== [] && count($explicitTaaIndexes) >= count($explicitHaaIndexes)) {
            foreach ($taaMarbutaIndexes as $index) {
                $result = is_array($endings[$index]['result'] ?? null) ? $endings[$index]['result'] : [];
                $endings[$index]['result'] = $this->forceTaaMarbutaAsTaaRawi($result);
            }
        }

        $hasResolvedTaa = false;

        foreach ($endings as $ending) {
            $result = is_array($ending['result'] ?? null) ? $ending['result'] : [];

            if ($this->componentLetter($result, 'rawi') === 'ت' && in_array('poem_level_taa_marbuta_resolved_as_taa', $this->traceRules($result), true)) {
                $hasResolvedTaa = true;
                break;
            }
        }

        if (! $hasResolvedTaa) {
            return $endings;
        }

        foreach ($endings as $index => $ending) {
            $result = is_array($ending['result'] ?? null) ? $ending['result'] : [];
            $word = $this->normalizedEndingWord($ending);

            if ($word === null || ! str_ends_with($word, 'تي')) {
                continue;
            }

            if ($this->componentLetter($result, 'rawi') === 'ت' && $this->componentLetter($result, 'wasl') === 'ي') {
                $endings[$index]['result'] = $this->absorbFinalYaaAsKasraIshbaa($result);
            }
        }

        return $endings;
    }

    /**
     * Improve display pattern for isolated final ى endings.
     *
     * Word-level detection correctly keeps ى as rawi, but in a one-ending poem
     * such as سعى the visible qafya pattern should be عى, not bare ى.
     *
     * This is display/pattern repair only. It does not change the rawi.
     *
     * @param  list<array<string, mixed>>  $endings
     * @return list<array<string, mixed>>
     */
    private function applyVisibleFinalMaqsuraPatternForSingleEnding(array $endings): array
    {
        $validIndexes = [];

        foreach ($endings as $index => $ending) {
            if (($ending['result']['status'] ?? null) === 'ok') {
                $validIndexes[] = $index;
            }
        }

        if (count($validIndexes) !== 1) {
            return $endings;
        }

        $index = $validIndexes[0];

        $result = is_array($endings[$index]['result'] ?? null)
            ? $endings[$index]['result']
            : [];

        if ($this->componentLetter($result, 'rawi') !== 'ى') {
            return $endings;
        }

        if ($this->componentLetter($result, 'wasl') !== null) {
            return $endings;
        }

        $word = $result['input']['normalized'] ?? $result['input']['word'] ?? null;

        if (! is_string($word) || $word === '') {
            return $endings;
        }

        $word = preg_replace('/[\x{064B}-\x{065F}\x{0670}\s\p{P}]+$/u', '', $word) ?? $word;

        if (! str_ends_with($word, 'ى')) {
            return $endings;
        }

        $tail = mb_substr($word, -2);

        if (mb_strlen($tail) !== 2 || $tail === 'ى') {
            return $endings;
        }

        $endings[$index]['result']['pattern']['surface'] = $tail;
        $endings[$index]['result']['qafya']['pattern']['surface'] = $tail;
        $endings[$index]['result']['signatures']['visual'] = $tail;

        if (isset($endings[$index]['result']['trace']) && is_array($endings[$index]['result']['trace'])) {
            $endings[$index]['result']['trace'][] = [
                'rule' => 'single_ending_visible_final_maqsura_pattern',
                'decision' => 'expanded_visible_pattern_from_bare_maqsura_to_previous_letter_plus_maqsura',
                'pattern' => $tail,
            ];
        }

        return $endings;
    }

    private function endsWithTaaMarbuta(string $word): bool
    {
        $word = preg_replace('/[\x{064B}-\x{065F}\x{0670}\s\p{P}]+$/u', '', $word) ?? $word;

        return str_ends_with($word, 'ة');
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function forceTaaMarbutaAsTaaRawi(array $result): array
    {
        $word = $result['input']['normalized'] ?? '';
        $chars = is_string($word) ? (mb_str_split($word) ?: []) : [];
        $before = count($chars) >= 2 ? ($chars[count($chars) - 2] ?? null) : null;

        $radf = match (true) {
            in_array($before, ['ا', 'آ', 'ى'], true) => 'ا',
            in_array($before, ['و', 'ي'], true) => $before,
            default => null,
        };

        $result['qafya']['category'] = 'muqayyada';
        $result['qafya']['subtype'] = $radf !== null ? 'muqayyada_mardoofa' : 'muqayyada_mujarrada';
        $result['qafya']['components']['taasis'] = null;
        $result['qafya']['components']['dakhiil'] = null;
        $result['qafya']['components']['radf'] = $radf === null ? null : [
            'letter' => $radf,
            'role' => 'radf',
            'family' => in_array($radf, ['ا', 'ى'], true) ? 'alif' : 'waw_yaa',
        ];
        $result['qafya']['components']['rawi'] = [
            'letter' => 'ت',
            'role' => 'rawi',
            'haraka' => null,
            'haraka_name' => null,
            'mujra' => null,
            'eligible' => true,
            'eligibility_reason' => 'taa_marbuta_resolved_as_taa_by_poem_context',
        ];
        $result['qafya']['components']['wasl'] = null;
        $result['qafya']['components']['khurooj'] = null;

        $result['qafya']['motions']['mujra'] = null;
        $result['qafya']['motions']['tawjih'] = null;
        $result['qafya']['motions']['hadhw'] = $radf !== null ? 'vowel_before_radf' : null;
        $result['qafya']['motions']['ishbaa'] = null;
        $result['qafya']['motions']['nafadh'] = null;

        $components = is_array($result['qafya']['components'] ?? null) ? $result['qafya']['components'] : [];
        $motions = is_array($result['qafya']['motions'] ?? null) ? $result['qafya']['motions'] : [];
        $signatures = $this->signatureBuilder->build($components, $motions);
        $surface = ($radf ?? '').'ت';

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
                'rule' => 'poem_level_taa_marbuta_resolved_as_taa',
                'decision' => 'taa_marbuta_joined_explicit_taa_rawi_family',
                'radf' => $radf,
            ];
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function absorbFinalYaaAsKasraIshbaa(array $result): array
    {
        $radf = $this->componentLetter($result, 'radf');

        $result['qafya']['category'] = 'muqayyada';
        $result['qafya']['components']['wasl'] = null;
        $result['qafya']['components']['rawi']['haraka'] = 'ِ';
        $result['qafya']['components']['rawi']['haraka_name'] = 'كسرة';
        $result['qafya']['components']['rawi']['mujra'] = 'kasra';
        $result['qafya']['motions']['mujra'] = 'kasra';
        $result['qafya']['motions']['tawjih'] = 'kasra';
        $result['qafya']['motions']['ishbaa'] = 'final_yaa_absorbed_as_kasra';

        $components = is_array($result['qafya']['components'] ?? null) ? $result['qafya']['components'] : [];
        $motions = is_array($result['qafya']['motions'] ?? null) ? $result['qafya']['motions'] : [];
        $signatures = $this->signatureBuilder->build($components, $motions);
        $surface = ($radf ?? '').'ت';

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
                'rule' => 'poem_level_final_yaa_absorbed_as_kasra_ishbaa',
                'decision' => 'final_yaa_does_not_split_taa_rawi_family',
            ];
        }

        return $result;
    }
}
