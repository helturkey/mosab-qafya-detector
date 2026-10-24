<?php

declare(strict_types=1);

namespace Mosab\QafyaDetector\Text;

use Mosab\QafyaDetector\Support\ArabicLetters;
use Mosab\QafyaDetector\Support\ArabicText;

/**
 * Extracts the qafya boundary from a hemistich.
 *
 * In scholarly mode, the boundary is derived from the last two phonetic
 * sakins in the final Arabic word. If that ending is not sufficiently marked,
 * the extractor uses an explicitly estimated last-word fallback unless the
 * caller opts into non-strict estimates for partially vocalized text.
 */
final class QafyaSegmentExtractor
{
    /**
     * Keep the former injection parameter for constructor compatibility.
     * Boundary extraction now uses PhoneticText directly so source mapping and
     * component detection share the same normalized representation.
     *
     * @deprecated The supplied normalizer is retained only for compatibility.
     */
    // @phpstan-ignore constructor.unusedParameter
    public function __construct(?ArudicNormalizer $normalizer = null) {}

    /**
     * @return array{surface: string, arudic: string, method: string, complete: bool, moving_count_between_sakins: ?int, last_sakin_index: ?int, previous_sakin_index: ?int, start_index: ?int, warnings: list<string>, source_span: array{start_index:?int,end_index:?int,byte_start:?int,byte_end:?int}|null}
     */
    public function extract(string $hemistich, string $mode = 'auto', bool $strictHarakat = true): array
    {
        $cleanHemistich = ArabicText::removeInvisible($hemistich);
        $tail = ArabicText::lastArabicWord($cleanHemistich, preserveTashkeel: true);
        if ($tail === null || $tail === '') {
            return $this->fallback($hemistich, ['empty_or_non_arabic_text']);
        }

        // Use the qafya-ending word, not any arbitrary mark elsewhere in the
        // hemistich, to decide whether a scholarly boundary can be calculated.
        $tailHasTashkeel = ArabicText::containsMeaningfulTashkeel($tail);
        $wholeHasTashkeel = ArabicText::containsMeaningfulTashkeel($hemistich);
        $phoneticText = new PhoneticText($tail);
        $coverage = $phoneticText->diacriticsCoverage();
        $tailPartiallyTashkeel = $tailHasTashkeel && ! $coverage['fully_marked_for_boundary'];

        // Partial tashkeel does not automatically prevent boundary extraction.
        // Continue only when the phonetic representation can still identify two
        // sakins; the moving-letter count remains unknown when marks are missing.

        // Heuristic mode normally returns a last-word estimate. A caller who
        // explicitly disables strict harakat may still ask for a best-effort
        // boundary when the line has marks but the ending does not. That path
        // remains incomplete and is labeled as estimated below.
        $allowPartialEstimate = ! $tailHasTashkeel && $wholeHasTashkeel && ! $strictHarakat;
        if ($mode === 'heuristic' && ! $tailHasTashkeel && ! $allowPartialEstimate) {
            $warnings = $wholeHasTashkeel
                ? ['heuristic_last_word_boundary', 'partially_diacritized_hemistich', 'undiacritized_rhyme_ending']
                : ['heuristic_last_word_boundary', 'undiacritized_text'];

            return $this->fallback($hemistich, $warnings);
        }

        if (! $tailHasTashkeel && $strictHarakat) {
            $warnings = $wholeHasTashkeel
                ? ['partially_diacritized_hemistich', 'undiacritized_rhyme_ending']
                : ['undiacritized_text'];

            if ($mode === 'scholarly') {
                $warnings[] = 'scholarly_mode_requires_tashkeel';
            }

            return $this->fallback($hemistich, $warnings);
        }

        if (! $tailHasTashkeel && ! $wholeHasTashkeel) {
            return $this->fallback($hemistich, ['undiacritized_text']);
        }

        $estimated = ! $tailHasTashkeel;
        $warnings = $estimated ? ['partial_tashkeel_estimated', 'boundary_not_fully_explicit'] : [];
        if ($tailPartiallyTashkeel) {
            $warnings[] = 'partially_diacritized_ending';
            $warnings[] = 'moving_count_may_be_incomplete';
        }
        $units = $phoneticText->units();
        if ($units === []) {
            return $this->fallback($hemistich, ['empty_or_non_arabic_text']);
        }

        $lastUnitIndex = count($units) - 1;

        // Classical qafya is read at pause. Digitized poems often keep a final
        // case vowel instead of an explicit pausal sukun, so infer pause only
        // inside the final Arabic word and disclose that inference.
        if ($lastUnitIndex >= 0 && $units[$lastUnitIndex]['is_sakin'] === false) {
            $units[$lastUnitIndex]['is_sakin'] = true;
            $units[$lastUnitIndex]['is_moving'] = false;
            $warnings[] = 'final_pause_sukun_assumed';
        }

        $sakinIndexes = [];
        foreach ($units as $index => $unit) {
            if ($unit['is_sakin']) {
                $sakinIndexes[] = $index;
            }
        }

        if (count($sakinIndexes) < 2) {
            return $this->fallback($hemistich, array_merge(['not_enough_detectable_sakins'], $warnings));
        }

        $lastSakin = $sakinIndexes[count($sakinIndexes) - 1];
        $previousSakin = $sakinIndexes[count($sakinIndexes) - 2];
        $start = max(0, $previousSakin - 1);
        $span = array_slice($units, $start, $lastSakin - $start + 1);

        // surface is the written boundary (each written code point once);
        // arudic is the phonetic sequence and therefore retains shadda doubling.
        $surfaceLetters = [];
        $seenSourceIndexes = [];
        $arudicLetters = [];
        foreach ($span as $unit) {
            $arudicLetters[] = $unit['letter'];
            $sourceIndex = $unit['source_index'];
            if (! isset($seenSourceIndexes[$sourceIndex])) {
                $surfaceLetters[] = $unit['surface_letter'] ?? $unit['letter'];
                $seenSourceIndexes[$sourceIndex] = true;
            }
        }

        $movingCount = 0;
        for ($i = $previousSakin + 1; $i < $lastSakin; $i++) {
            // Count only explicitly identified moving units. An unmarked
            // consonant is unknown, not automatically a moving letter.
            if (($units[$i]['is_moving'] ?? false) === true) {
                $movingCount++;
            }
        }

        $tailCleanStart = mb_strrpos($cleanHemistich, $tail);
        $cleanToOriginal = $this->cleanedToOriginalMap($hemistich);
        $originalPositions = [];
        if ($tailCleanStart !== false) {
            foreach ($span as $unit) {
                $localIndex = (int) ($unit['source_index'] ?? -1);
                if ($localIndex < 0) {
                    continue;
                }
                $cleanIndex = $tailCleanStart + $localIndex;
                if (isset($cleanToOriginal[$cleanIndex])) {
                    $originalPositions[] = $cleanToOriginal[$cleanIndex];
                }
            }
        }
        $sourceSpan = null;
        if ($originalPositions !== []) {
            usort($originalPositions, static fn (array $a, array $b): int => $a['source_index'] <=> $b['source_index']);
            $firstPosition = $originalPositions[0];
            $lastPosition = $originalPositions[count($originalPositions) - 1];
            $sourceStart = $firstPosition['source_index'];
            $sourceEnd = $lastPosition['source_index'] + 1;
            $byteStart = $firstPosition['byte_start'];
            $byteEnd = $lastPosition['byte_end'];
            $originalChars = mb_str_split($hemistich) ?: [];
            // Include combining marks attached to the last mapped written glyph.
            for ($i = $sourceEnd; $i < count($originalChars); $i++) {
                $char = $originalChars[$i];
                if (ArabicLetters::isHaraka($char) || in_array($char, ArabicLetters::INVISIBLE, true)) {
                    $byteEnd += strlen($char);
                    $sourceEnd++;

                    continue;
                }
                break;
            }
            $sourceSpan = [
                'start_index' => $sourceStart,
                'end_index' => $sourceEnd,
                'byte_start' => $byteStart,
                'byte_end' => $byteEnd,
            ];
        }

        return [
            'surface' => implode('', $surfaceLetters),
            'arudic' => implode('', $arudicLetters),
            'method' => $estimated ? 'mixed_diacritics_estimated' : 'khalil_last_two_sakins',
            'complete' => ! $estimated,
            'moving_count_between_sakins' => $tailPartiallyTashkeel ? null : $movingCount,
            'last_sakin_index' => $lastSakin,
            'previous_sakin_index' => $previousSakin,
            'start_index' => $start,
            'warnings' => array_values(array_unique($warnings)),
            'source_span' => $sourceSpan,
        ];
    }

    /**
     * Map each code point in ArabicText::removeInvisible($text) back to the
     * source code point and UTF-8 byte offsets before invisible controls were removed.
     *
     * @return list<array{source_index:int,byte_start:int,byte_end:int}>
     */
    private function cleanedToOriginalMap(string $text): array
    {
        $map = [];
        $sourceIndex = 0;
        $byteOffset = 0;
        foreach (mb_str_split($text) ?: [] as $char) {
            $byteStart = $byteOffset;
            $byteOffset += strlen($char);
            if ($char === "\u{00A0}") {
                $map[] = ['source_index' => $sourceIndex, 'byte_start' => $byteStart, 'byte_end' => $byteOffset];
            } elseif (! in_array($char, ArabicLetters::INVISIBLE, true)) {
                $map[] = ['source_index' => $sourceIndex, 'byte_start' => $byteStart, 'byte_end' => $byteOffset];
            }
            $sourceIndex++;
        }

        return $map;
    }

    /**
     * @param  list<string>  $warnings
     * @return array{surface: string, arudic: string, method: string, complete: bool, moving_count_between_sakins: ?int, last_sakin_index: ?int, previous_sakin_index: ?int, start_index: ?int, warnings: list<string>, source_span: null}
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
            'warnings' => array_values(array_unique($warnings)),
            'source_span' => null,
        ];
    }
}
