<?php

declare(strict_types=1);

namespace Mosab\QafyaDetector\Support;

/**
 * Keeps orthographic tāʾ marbūṭa visible without changing qafya components.
 *
 * A final ة may be modeled as ه either as wasl (for example البركة) or as the
 * rawi itself (for example نجاة). The surface pattern preserves ة in both
 * cases. The established radf=ا, rawi=ي, wasl=ه spelling pattern for endings
 * such as الزراية remains ايه. This formatter affects display only.
 */
final class QafyaSurfaceFormatter
{
    public static function preserveTaaMarbuta(
        string $pattern,
        mixed $endingWord,
        ?string $rawi = null,
        ?string $radf = null,
        ?string $wasl = null,
    ): string {
        if ($pattern === '' || ! is_string($endingWord) || $endingWord === '') {
            return $pattern;
        }

        $candidateWord = ArabicText::lastArabicWord($endingWord, preserveTashkeel: true) ?? $endingWord;
        $normalizedWord = ArabicText::normalizeForQafya($candidateWord);

        if (! str_ends_with($normalizedWord, 'ة') || ! str_ends_with($pattern, 'ه')) {
            return $pattern;
        }

        // Preserve the established aya/yāʾ/haa surface: radf=ا, rawi=ي,
        // wasl=ه is displayed as ايه despite the source word ending in ة.
        if ($rawi === 'ي' && $radf === 'ا' && $wasl === 'ه') {
            return $pattern;
        }

        // The source spelling wins for all other final-ة patterns, including
        // cases where paused ة is modeled structurally as rawi=ه (نجاة -> اة).
        $letters = mb_str_split($pattern);
        array_pop($letters);

        return implode('', $letters).'ة';
    }
}
