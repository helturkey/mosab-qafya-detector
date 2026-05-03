<?php

declare(strict_types=1);

namespace Mosab\QafyaDetector\Text;

use Mosab\QafyaDetector\Support\ArabicLetters;
use Mosab\QafyaDetector\Support\ArabicText;

/**
 * Lightweight arudic normalizer focused on qafya boundaries.
 *
 * It does not try to scan the full meter. It preserves enough phonetic
 * structure at the end of a hemistich to find the qafya boundary by the
 * Khalilian "last two sakins" definition when diacritics are present.
 */
final class ArudicNormalizer
{
    /**
     * @return list<array{letter: string, haraka: ?string, is_sakin: bool, is_moving: bool, original_index: int}>
     */
    public function units(string $text): array
    {
        $chars = ArabicText::chars(ArabicText::removeInvisible($text));
        $units = [];

        foreach ($chars as $char) {
            if (ArabicLetters::isHaraka($char)) {
                if ($units !== []) {
                    $last = count($units) - 1;
                    if ($char !== ArabicLetters::SHADDA) {
                        $units[$last]['haraka'] = $char;
                        $units[$last]['is_sakin'] = ArabicLetters::isSukun($char);
                        $units[$last]['is_moving'] = ArabicLetters::isShortHaraka($char) || in_array($char, ArabicLetters::TANWEEN, true);
                    }
                }

                continue;
            }

            if (! ArabicLetters::isArabicLetter($char)) {
                continue;
            }

            $letter = ArabicLetters::normalizeAlefLike($char);
            $units[] = [
                'letter' => $letter === 'ة' ? 'ه' : $letter,
                'haraka' => null,
                'is_sakin' => $this->letterDefaultsToSakin($letter),
                'is_moving' => false,
                'original_index' => count($units),
            ];
        }

        return $units;
    }

    private function letterDefaultsToSakin(string $letter): bool
    {
        return in_array($letter, ['ا', 'ى'], true);
    }
}
