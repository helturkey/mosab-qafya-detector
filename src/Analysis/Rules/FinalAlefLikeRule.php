<?php

declare(strict_types=1);

namespace Mosab\QafyaDetector\Analysis\Rules;

use Mosab\QafyaDetector\Support\ArabicText;

/** Resolves final alef/alef-maqsura families using poem-level stability. */
final class FinalAlefLikeRule extends AbstractPoemLevelRule
{
    /**
     * @param  list<array<string, mixed>>  $endings
     * @return list<array<string, mixed>>
     */
    public function apply(array $endings): array
    {
        $endings = $this->applyStableFinalAlefLikeWaslOverride($endings);
        $endings = $this->applyFinalAlefLikeRawiOverride($endings);

        return $endings;
    }

    /**
     * Normalize stable final alef-like families at poem level.
     *
     * When most endings close with ا/ى and the pre-final consonant is stable,
     * the committed rawi is that pre-final consonant and the final alef-like
     * sound is wasl. This covers poems such as:
     *
     *   غضّا / نفضا / عرضا / أمضى / يُقضى / ترضى
     *
     * Word-level detection may preserve written ى as rawi for isolated words,
     * but poem context can prove that ا and ى are only orthographic variants of
     * the final alef-like wasl after a stable rawi.
     *
     * @param  list<array<string, mixed>>  $endings
     * @return list<array<string, mixed>>
     */
    private function applyStableFinalAlefLikeWaslOverride(array $endings): array
    {
        $valid = array_values(array_filter(
            $endings,
            static fn (array $ending): bool => ($ending['result']['status'] ?? null) === 'ok'
        ));

        if (count($valid) < 3) {
            return $endings;
        }

        /** @var array<string, int> $preFinalLetters */
        $preFinalLetters = [];
        /** @var array<int, array{surface: string, identity: string}> $eligibleByIndex */
        $eligibleByIndex = [];

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
            $last = $chars[$length - 1] ?? null;

            if (! in_array($last, ['ا', 'ى'], true) || $length < 2) {
                continue;
            }

            $preFinal = $chars[$length - 2] ?? null;

            if (! is_string($preFinal) || $preFinal === '') {
                continue;
            }

            /*
             * عزائى / بسمائى / دائى:
             * final ى after hamza-seat is handled as ياء وصل, not as alef-like wasl.
             * Do not let the generic stable-final-alef override rewrite it to rawi=ئ/ء + wasl=ا.
             */
            if ($last === 'ى' && ArabicText::isHamzaSeat($preFinal)) {
                continue;
            }

            $preFinalIdentity = ArabicText::canonicalRawiIdentity($preFinal) ?? $preFinal;

            $eligibleByIndex[$index] = [
                'surface' => $preFinal,
                'identity' => $preFinalIdentity,
            ];

            $preFinalLetters[$preFinalIdentity] = ($preFinalLetters[$preFinalIdentity] ?? 0) + 1;
        }

        $eligibleCount = count($eligibleByIndex);
        $eligibleRatio = $eligibleCount / max(1, count($valid));

        if ($eligibleCount < 3 || $eligibleRatio < 0.6 || $preFinalLetters === []) {
            return $endings;
        }

        arsort($preFinalLetters);
        $dominantPreFinal = array_key_first($preFinalLetters);
        $dominantPreFinalCount = $dominantPreFinal !== null ? $preFinalLetters[$dominantPreFinal] : 0;
        $dominantPreFinalRatio = $dominantPreFinalCount / max(1, $eligibleCount);

        // If the pre-final consonant varies, this is the opposite family:
        // the final alef-like letter itself becomes the poem-level rawi.
        if ($dominantPreFinal === null || $dominantPreFinalRatio < 0.8) {
            return $endings;
        }

        $stableRadf = $this->stableRadfBeforeFinalAlefLikeWasl($endings, $eligibleByIndex, $dominantPreFinal);

        foreach ($eligibleByIndex as $index => $preFinalData) {
            if (! is_array($preFinalData)) {
                continue;
            }

            $preFinalIdentity = is_string($preFinalData['identity'] ?? null) ? $preFinalData['identity'] : null;
            $preFinalSurface = is_string($preFinalData['surface'] ?? null) ? $preFinalData['surface'] : $preFinalIdentity;

            if ($preFinalIdentity !== $dominantPreFinal) {
                continue;
            }

            $result = is_array($endings[$index]['result'] ?? null) ? $endings[$index]['result'] : [];
            $endings[$index]['result'] = $this->forceFinalAlefLikeWaslAfterRawi(
                $result,
                $dominantPreFinal,
                $stableRadf,
                $preFinalSurface,
            );
        }

        return $endings;
    }

    /**
     * Preserve a radf in the poem-level dominant pattern only when that exact
     * radf letter is itself stable before the stable rawi.
     *
     * This distinction is important:
     * - ناداها / جناها / تراها => rawi=ه, radf=ا, wasl=ا, pattern=اها
     * - جديدا / يعودا / جيدا / جودا => rawi=د, wasl=ا, pattern=دا
     *
     * In the second family, ي/و alternate before the rawi. They may be useful in
     * per-ending details, but they must not enter the public poem-level pattern.
     *
     * @param  list<array<string, mixed>>  $endings
     * @param  array<int, array{surface: string, identity: string}>  $eligibleByIndex
     */
    private function stableRadfBeforeFinalAlefLikeWasl(array $endings, array $eligibleByIndex, string $rawi): ?string
    {
        /** @var array<string, int> $radfCounts */
        $radfCounts = [];
        $eligibleForRawi = 0;

        foreach ($eligibleByIndex as $index => $preFinalData) {
            $preFinalIdentity = $preFinalData['identity'];

            if ($preFinalIdentity !== $rawi) {
                continue;
            }

            $word = $this->normalizedEndingWord($endings[$index]);

            if ($word === null || $word === '') {
                continue;
            }

            $eligibleForRawi++;
            $result = is_array($endings[$index]['result'] ?? null) ? $endings[$index]['result'] : [];
            $radf = $this->radfBeforeStableFinalAlefLikeRawi($result, $rawi);

            if ($radf !== null) {
                $radfCounts[$radf] = ($radfCounts[$radf] ?? 0) + 1;
            }
        }

        if ($eligibleForRawi === 0 || $radfCounts === []) {
            return null;
        }

        arsort($radfCounts);
        $dominantRadf = array_key_first($radfCounts);
        $dominantCount = $dominantRadf !== null ? $radfCounts[$dominantRadf] : 0;

        // Preserve only an exact stable radf. If ي/و alternate, do not expose a
        // misleading dominant pattern such as يدا for the whole poem.
        return $dominantRadf !== null && ($dominantCount / max(1, $eligibleForRawi)) >= 0.8
            ? $dominantRadf
            : null;
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function forceFinalAlefLikeWaslAfterRawi(
        array $result,
        string $rawi,
        ?string $preservedRadf,
        ?string $rawiSurface = null,
    ): array {
        $rawiSurface ??= $rawi;
        $rawi = ArabicText::canonicalRawiIdentity($rawi) ?? $rawi;

        $result['qafya']['category'] = 'mutlaqa';
        $result['qafya']['subtype'] = $preservedRadf !== null
            ? 'mutlaqa_mardoofa_madd'
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
            'eligibility_reason' => 'stable_pre_final_letter_before_final_alef_like_wasl',
        ];
        $result['qafya']['components']['wasl'] = [
            'letter' => 'ا',
            'role' => 'wasl',
            'kind' => 'alef_like',
        ];
        $result['qafya']['components']['khurooj'] = null;

        $result['qafya']['motions']['mujra'] = null;
        $result['qafya']['motions']['tawjih'] = null;
        $result['qafya']['motions']['hadhw'] = $preservedRadf !== null ? 'vowel_before_radf' : null;
        $result['qafya']['motions']['ishbaa'] = null;
        $result['qafya']['motions']['nafadh'] = null;

        $components = is_array($result['qafya']['components'] ?? null) ? $result['qafya']['components'] : [];
        $motions = is_array($result['qafya']['motions'] ?? null) ? $result['qafya']['motions'] : [];
        $signatures = $this->signatureBuilder->build($components, $motions);
        $surface = ($preservedRadf ?? '').$rawiSurface.'ا';

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
                'rule' => 'poem_level_stable_final_alef_like_wasl',
                'decision' => 'stable_pre_final_letter_is_rawi_and_final_alef_like_is_wasl',
                'rawi' => $rawi,
                'preserved_radf' => $preservedRadf,
            ];
        }

        return $result;
    }

    /** @param array<string, mixed> $result */
    private function radfBeforeStableFinalAlefLikeRawi(array $result, string $rawi): ?string
    {
        $word = $result['input']['normalized'] ?? null;
        if (! is_string($word) || $word === '') {
            return null;
        }

        $chars = mb_str_split($word) ?: [];
        $length = count($chars);
        if ($length < 3 || ! in_array($chars[$length - 1] ?? null, ['ا', 'ى'], true)) {
            return null;
        }

        $rawiIndex = $length - 2;
        $currentRawi = ArabicText::canonicalRawiIdentity($chars[$rawiIndex] ?? null);
        $expectedRawi = ArabicText::canonicalRawiIdentity($rawi);

        if ($currentRawi !== $expectedRawi) {
            return null;
        }

        $beforeRawi = $chars[$rawiIndex - 1] ?? null;
        if (! is_string($beforeRawi) || ! in_array($beforeRawi, ['ا', 'ى', 'و', 'ي'], true)) {
            return null;
        }

        return $beforeRawi === 'ى' ? 'ا' : $beforeRawi;
    }

    /**
     * Promote final alef/alef-maqsura to the poem-level rawi when the poem
     * clearly rhymes on the final vowel itself and the letter before it varies.
     *
     * This covers families such as هدى / غلا / سرى / ترى / للورى / هلا.
     * In isolation, final ا/ى can be read as wasl after the previous consonant;
     * across the poem, diversity before the final alef-like letter proves the
     * final alef-like sound is the committed rhyme anchor.
     *
     * @param  list<array<string, mixed>>  $endings
     * @return list<array<string, mixed>>
     */
    private function applyFinalAlefLikeRawiOverride(array $endings): array
    {
        $valid = array_values(array_filter(
            $endings,
            static fn (array $ending): bool => ($ending['result']['status'] ?? null) === 'ok'
        ));

        if (count($valid) < 3) {
            return $endings;
        }

        /** @var list<int> $eligibleIndexes */
        $eligibleIndexes = [];
        /** @var array<string, int> $preFinalLetters */
        $preFinalLetters = [];

        foreach ($endings as $index => $ending) {
            if (($ending['result']['status'] ?? null) !== 'ok') {
                continue;
            }

            $word = $this->normalizedEndingWord($ending);
            if ($word === null || $word === '') {
                continue;
            }

            $chars = mb_str_split($word) ?: [];
            $last = $chars[count($chars) - 1] ?? null;

            if (! in_array($last, ['ا', 'ى'], true)) {
                continue;
            }

            $eligibleIndexes[] = $index;
            $preFinal = $chars[count($chars) - 2] ?? null;
            if (is_string($preFinal) && $preFinal !== '') {
                $preFinalLetters[$preFinal] = ($preFinalLetters[$preFinal] ?? 0) + 1;
            }
        }

        $ratio = count($eligibleIndexes) / max(1, count($valid));

        // Stable pre-final consonant means the usual word-level reading is more
        // likely correct. Diverse pre-final letters mean the final alef-like
        // letter is the poem-level rawi.
        if (count($eligibleIndexes) < 3 || $ratio < 0.6 || count($preFinalLetters) < 2) {
            return $endings;
        }

        foreach ($eligibleIndexes as $index) {
            $result = is_array($endings[$index]['result'] ?? null) ? $endings[$index]['result'] : [];
            $endings[$index]['result'] = $this->promoteFinalAlefLikeToRawi($result);
        }

        return $endings;
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function promoteFinalAlefLikeToRawi(array $result): array
    {
        $result['qafya']['category'] = 'muqayyada';
        $result['qafya']['subtype'] = 'muqayyada_mujarrada';
        $result['qafya']['components']['taasis'] = null;
        $result['qafya']['components']['dakhiil'] = null;
        $result['qafya']['components']['radf'] = null;
        $result['qafya']['components']['rawi'] = [
            'letter' => 'ى',
            'role' => 'rawi',
            'haraka' => null,
            'haraka_name' => null,
            'mujra' => null,
            'eligible' => true,
            'eligibility_reason' => 'final_alef_like_promoted_by_poem_context',
        ];
        $result['qafya']['components']['wasl'] = null;
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
            'surface' => 'ى',
            'component' => $signatures['component'],
            'strict' => $signatures['strict'],
            'cluster' => $signatures['cluster'],
            'canonical' => $signatures['component'],
        ];
        $result['qafya']['pattern'] = $result['pattern'];

        if (isset($result['trace']) && is_array($result['trace'])) {
            $result['trace'][] = [
                'rule' => 'poem_level_final_alef_like_rawi',
                'decision' => 'final_alef_or_alef_maqsura_promoted_to_rawi_because_pre_final_letters_vary',
            ];
        }

        return $result;
    }
}
