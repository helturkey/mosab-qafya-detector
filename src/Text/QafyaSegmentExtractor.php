<?php

declare(strict_types=1);

namespace Mosab\QafyaDetector\Text;

use Mosab\QafyaDetector\Support\ArabicText;

/**
 * Extracts qafya boundary from a hemistich.
 *
 * Scholarly method: from the last sakin to the nearest previous sakin, plus the
 * moving letter before the previous sakin. If the text is undiacritized, a safe
 * fallback takes the normalized last word and marks the boundary as estimated.
 */
final class QafyaSegmentExtractor
{
    public function __construct(private readonly ArudicNormalizer $normalizer = new ArudicNormalizer) {}

    /**
     * @return array{surface: string, arudic: string, method: string, complete: bool, moving_count_between_sakins: ?int, last_sakin_index: ?int, previous_sakin_index: ?int, start_index: ?int, warnings: list<string>}
     */
    public function extract(string $hemistich): array
    {
        if (! ArabicText::containsMeaningfulTashkeel($hemistich)) {
            return $this->fallback($hemistich, ['undiacritized_text']);
        }

        $tail = ArabicText::lastArabicWord($hemistich, preserveTashkeel: true);
        if ($tail === null || $tail === '') {
            return $this->fallback($hemistich, ['empty_or_non_arabic_text']);
        }

        $units = $this->normalizer->units($tail);
        if ($units === []) {
            return $this->fallback($hemistich, ['empty_or_non_arabic_text']);
        }

        $warnings = [];
        $lastUnitIndex = count($units) - 1;

        // Classical qafya is read at pause. Most digitized poems keep the final
        // case vowel (e.g. الغَلَسِ) and omit the pausal sukun. We therefore
        // assume final pause only inside the last Arabic word, never across the
        // whole hemistich. This prevents a false segment such as "رىمنبيتهفيا".
        if ($lastUnitIndex >= 0 && $units[$lastUnitIndex]['is_sakin'] === false) {
            $units[$lastUnitIndex]['is_sakin'] = true;
            $units[$lastUnitIndex]['is_moving'] = false;
            $warnings[] = 'final_pause_sukun_assumed';
        }

        $sakinIndexes = [];
        foreach ($units as $i => $unit) {
            if ($unit['is_sakin']) {
                $sakinIndexes[] = $i;
            }
        }

        if (count($sakinIndexes) < 2) {
            return $this->fallback($hemistich, array_merge(['not_enough_detectable_sakins'], $warnings));
        }

        $lastSakin = $sakinIndexes[count($sakinIndexes) - 1];
        $previousSakin = $sakinIndexes[count($sakinIndexes) - 2];
        $start = max(0, $previousSakin - 1);
        /** @var list<string> $letters */
        $letters = array_map(static fn (array $u): string => $u['letter'], array_slice($units, $start, $lastSakin - $start + 1));
        $movingCount = 0;
        for ($i = $previousSakin + 1; $i < $lastSakin; $i++) {
            if (($units[$i]['is_moving'] ?? false) === true || ($units[$i]['is_sakin'] ?? false) === false) {
                $movingCount++;
            }
        }

        return [
            'surface' => implode('', $letters),
            'arudic' => implode('', $letters),
            'method' => 'khalil_last_two_sakins',
            'complete' => true,
            'moving_count_between_sakins' => $movingCount,
            'last_sakin_index' => $lastSakin,
            'previous_sakin_index' => $previousSakin,
            'start_index' => $start,
            'warnings' => $warnings,
        ];
    }

    /**
     * @param  list<string>  $warnings
     * @return array{surface: string, arudic: string, method: string, complete: bool, moving_count_between_sakins: ?int, last_sakin_index: ?int, previous_sakin_index: ?int, start_index: ?int, warnings: list<string>}
     */
    private function fallback(string $text, array $warnings): array
    {
        $lastWord = ArabicText::lastArabicWord($text) ?? '';
        $lastWord = preg_replace('/^[أإآ]/u', 'ا', $lastWord) ?? $lastWord;

        return [
            'surface' => $lastWord,
            'arudic' => $lastWord,
            'method' => 'estimated_last_word_fallback',
            'complete' => false,
            'moving_count_between_sakins' => null,
            'last_sakin_index' => null,
            'previous_sakin_index' => null,
            'start_index' => null,
            'warnings' => $warnings,
        ];
    }
}
