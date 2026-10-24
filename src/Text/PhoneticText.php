<?php

declare(strict_types=1);

namespace Mosab\QafyaDetector\Text;

use Mosab\QafyaDetector\Support\ArabicLetters;
use Mosab\QafyaDetector\Support\ArabicText;

/**
 * A shared normalized-letter/phonetic-unit view over the original Arabic text.
 *
 * Indices named source_index are Unicode code-point offsets into the exact
 * input string. Byte offsets are also supplied for safe slicing. Shadda
 * expands to two phonetic units that intentionally point to the same source
 * span. This class does not guess missing vowels: unknown marks stay unknown.
 */
final class PhoneticText
{
    /** @var list<string> */
    private array $sourceChars;

    /** @var list<array{letter:string,source_index:int,source_byte_start:int,source_byte_end:int,source_letter:string}> */
    private array $letterMap;

    /** @var list<string> */
    private array $normalizedLetters;

    private int $sourceLengthBytes;

    public function __construct(private readonly string $source)
    {
        $this->sourceChars = mb_str_split($source) ?: [];
        $this->sourceLengthBytes = strlen($source);
        $this->normalizedLetters = ArabicText::chars(ArabicText::normalizeForQafya($source));
        $this->letterMap = $this->buildLetterMap();
    }

    /** @return list<string> */
    public function normalizedLetters(): array
    {
        return $this->normalizedLetters;
    }

    /** @return list<array{letter:string,source_index:int,source_byte_start:int,source_byte_end:int,source_letter:string}> */
    public function letterMap(): array
    {
        return $this->letterMap;
    }

    /**
     * Return haraka information for a normalized-letter ordinal, not the last
     * matching character in the word. Repeated letters therefore remain safe.
     *
     * @return array{mark:?string,name:?string,class:?string}
     */
    public function harakaAtLetterIndex(int $letterIndex): array
    {
        $entry = $this->letterMap[$letterIndex] ?? null;

        if ($entry === null) {
            return $this->emptyHaraka();
        }

        $marks = $this->marksAfterSourceIndex($entry['source_index']);
        foreach (array_reverse($marks) as $mark) {
            if ($mark === ArabicLetters::SHADDA) {
                continue;
            }

            return [
                'mark' => $mark,
                'name' => ArabicLetters::harakaName($mark),
                'class' => ArabicLetters::harakaClass($mark),
            ];
        }

        return $this->emptyHaraka();
    }

    /**
     * Build phonetic units while retaining one-to-one links to written glyphs.
     * Unmarked consonants are marked as unknown, not falsely described as
     * moving or sakin. The boundary extractor may use its existing documented
     * fallback policy, but callers can now see the uncertainty explicitly.
     *
     * @return list<array{letter:string,surface_letter:string,haraka:?string,is_sakin:bool,is_moving:bool,is_long_vowel:bool,long_vowel_kind:?string,tanween_type:?string,vocalization_status:string,original_index:int,source_index:int,source_byte_start:int,source_byte_end:int}>
     */
    public function units(): array
    {
        $units = [];
        $lastLetterIndex = count($this->letterMap) - 1;

        foreach ($this->letterMap as $letterIndex => $entry) {
            $marks = $this->marksAfterSourceIndex($entry['source_index']);
            $hasShadda = in_array(ArabicLetters::SHADDA, $marks, true);
            $haraka = null;
            foreach (array_reverse($marks) as $mark) {
                if ($mark !== ArabicLetters::SHADDA) {
                    $haraka = $mark;
                    break;
                }
            }

            $surfaceLetter = $entry['source_letter'];
            $letter = ArabicLetters::normalizeAlefLike($entry['letter']);
            $isFinal = $letterIndex === $lastLetterIndex;
            $previousHaraka = $letterIndex > 0 ? $this->harakaAtLetterIndex($letterIndex - 1)['mark'] : null;
            $tanweenType = match ($haraka) {
                'ً' => 'fathatan',
                'ٌ' => 'dammatan',
                'ٍ' => 'kasratan',
                default => null,
            };
            $longVowelKind = $this->longVowelKind($letter, $previousHaraka, $hasShadda);
            $isLongVowel = $longVowelKind !== null;

            if ($surfaceLetter === 'ة') {
                $letter = (! $isFinal || ($haraka !== null && ! ArabicLetters::isSukun($haraka))) ? 'ت' : 'ه';
            }

            $moving = $haraka !== null && (
                ArabicLetters::isShortHaraka($haraka)
                || in_array($haraka, ArabicLetters::TANWEEN, true)
            );
            $sakin = $haraka !== null
                ? ArabicLetters::isSukun($haraka)
                : ($isLongVowel || $letter === 'ا' || $letter === 'ى');
            $vocalizationStatus = $haraka !== null
                ? (in_array($haraka, ArabicLetters::TANWEEN, true) ? 'explicit_tanween' : 'explicit')
                : (($isLongVowel || in_array($letter, ['ا', 'ى'], true)) ? 'inherent_or_orthographic' : 'unmarked');

            if ($hasShadda) {
                $units[] = [
                    'letter' => $letter,
                    'surface_letter' => $surfaceLetter,
                    'haraka' => ArabicLetters::SUKUN,
                    'is_sakin' => true,
                    'is_moving' => false,
                    'is_long_vowel' => false,
                    'long_vowel_kind' => null,
                    'tanween_type' => null,
                    'vocalization_status' => 'shadda_first_half',
                    'original_index' => count($units),
                    'source_index' => $entry['source_index'],
                    'source_byte_start' => $entry['source_byte_start'],
                    'source_byte_end' => $entry['source_byte_end'],
                ];
                $units[] = [
                    'letter' => $letter,
                    'surface_letter' => $surfaceLetter,
                    'haraka' => $haraka,
                    'is_sakin' => $haraka !== null ? ArabicLetters::isSukun($haraka) : ($isLongVowel || in_array($letter, ['ا', 'ى'], true)),
                    'is_moving' => $moving,
                    'is_long_vowel' => $isLongVowel,
                    'long_vowel_kind' => $longVowelKind,
                    'tanween_type' => $tanweenType,
                    'vocalization_status' => $vocalizationStatus,
                    'original_index' => count($units),
                    'source_index' => $entry['source_index'],
                    'source_byte_start' => $entry['source_byte_start'],
                    'source_byte_end' => $entry['source_byte_end'],
                ];

                continue;
            }

            $units[] = [
                'letter' => $letter,
                'surface_letter' => $surfaceLetter,
                'haraka' => $haraka,
                'is_sakin' => $sakin,
                'is_moving' => $moving,
                'is_long_vowel' => $isLongVowel,
                'long_vowel_kind' => $longVowelKind,
                'tanween_type' => $tanweenType,
                'vocalization_status' => $vocalizationStatus,
                'original_index' => count($units),
                'source_index' => $entry['source_index'],
                'source_byte_start' => $entry['source_byte_start'],
                'source_byte_end' => $entry['source_byte_end'],
            ];
        }

        return $units;
    }

    /**
     * Coverage treats the final letter as a pausal position, so it need not
     * carry a written vowel. Long orthographic letters are not counted as
     * missing short-vowel marks. This is a diagnostic, not a claim that all
     * other spelling conventions are fully represented by Unicode marks.
     *
     * @return array{letter_count:int,marked_count:int,unmarked_internal_consonants:int,coverage:float,fully_marked_for_boundary:bool}
     */
    public function diacriticsCoverage(): array
    {
        $letterCount = count($this->letterMap);
        $markedCount = 0;
        $unmarkedInternal = 0;

        foreach ($this->letterMap as $i => $entry) {
            $mark = $this->harakaAtLetterIndex($i)['mark'];
            if ($mark !== null) {
                $markedCount++;

                continue;
            }

            $letter = ArabicLetters::normalizeAlefLike($entry['letter']);
            $previousMark = $i > 0 ? $this->harakaAtLetterIndex($i - 1)['mark'] : null;
            $marks = $this->marksAfterSourceIndex($entry['source_index']);
            $isKnownLongVowel = $this->longVowelKind($letter, $previousMark, in_array(ArabicLetters::SHADDA, $marks, true)) !== null
                || in_array($letter, ['ا', 'ى'], true);
            if ($isKnownLongVowel) {
                // Orthographic long-vowel evidence can determine a boundary
                // even though the long-vowel glyph itself has no short mark.
                $markedCount++;

                continue;
            }

            $isFinal = $i === $letterCount - 1;
            if (! $isFinal) {
                $unmarkedInternal++;
            }
        }

        $coverageDenominator = max(1, $letterCount - ($letterCount > 0 ? 1 : 0));
        $coverage = round(min(1.0, $markedCount / $coverageDenominator), 4);

        return [
            'letter_count' => $letterCount,
            'marked_count' => $markedCount,
            'unmarked_internal_consonants' => $unmarkedInternal,
            'coverage' => $coverage,
            'fully_marked_for_boundary' => $letterCount > 0 && $unmarkedInternal === 0,
        ];
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'source_text' => $this->source,
            'normalized' => implode('', $this->normalizedLetters),
            'letter_map' => $this->letterMap,
            'units' => $this->units(),
            'diacritics_coverage' => $this->diacriticsCoverage(),
            'source_length_codepoints' => count($this->sourceChars),
            'source_length_bytes' => $this->sourceLengthBytes,
        ];
    }

    /** @return list<array{letter:string,source_index:int,source_byte_start:int,source_byte_end:int,source_letter:string}> */
    private function buildLetterMap(): array
    {
        $candidates = [];
        $byteOffset = 0;
        $charsCount = count($this->sourceChars);

        for ($i = 0; $i < $charsCount; $i++) {
            $char = $this->sourceChars[$i];
            $start = $byteOffset;
            $byteOffset += strlen($char);
            $end = $byteOffset;

            if ($char === "\u{00A0}" || preg_match('/^\s$/u', $char) === 1) {
                if ($candidates !== [] && end($candidates)['letter'] !== ' ') {
                    $candidates[] = [
                        'letter' => ' ',
                        'source_index' => $i,
                        'source_byte_start' => $start,
                        'source_byte_end' => $end,
                        'source_letter' => $char,
                    ];
                }

                continue;
            }
            if (in_array($char, ArabicLetters::INVISIBLE, true) || ArabicLetters::isHaraka($char)) {
                continue;
            }

            // Arabic dagger alif after ى is normalized as part of the same
            // written cluster by ArabicText::normalizeForQafya.
            if ($char === 'ى' && ($this->sourceChars[$i + 1] ?? null) === 'ٰ') {
                $candidates[] = [
                    'letter' => 'ا',
                    'source_index' => $i,
                    'source_byte_start' => $start,
                    'source_byte_end' => $end + strlen('ٰ'),
                    'source_letter' => 'ى',
                ];
                $next = $this->sourceChars[$i + 1];
                $byteOffset += strlen($next);
                $i++;

                continue;
            }

            $piece = ArabicText::chars(ArabicText::normalizeForQafya($char));
            foreach ($piece as $pieceChar) {
                if ($pieceChar === ' ') {
                    if ($candidates !== [] && end($candidates)['letter'] !== ' ') {
                        $candidates[] = [
                            'letter' => ' ',
                            'source_index' => $i,
                            'source_byte_start' => $start,
                            'source_byte_end' => $end,
                            'source_letter' => $char,
                        ];
                    }

                    continue;
                }
                if (! ArabicLetters::isArabicLetter($pieceChar)) {
                    continue;
                }
                $candidates[] = [
                    'letter' => $pieceChar,
                    'source_index' => $i,
                    'source_byte_start' => $start,
                    'source_byte_end' => $end,
                    'source_letter' => $char,
                ];
            }
        }

        if ($candidates !== [] && end($candidates)['letter'] === ' ') {
            array_pop($candidates);
        }
        $normalized = $this->normalizedLetters;
        // For ending-level calls this normally maps one-to-one. Greedy matching
        // also tolerates punctuation/invisible controls without losing offsets.
        $map = [];
        $cursor = 0;
        foreach ($normalized as $char) {
            while (isset($candidates[$cursor]) && $candidates[$cursor]['letter'] !== $char) {
                $cursor++;
            }
            if (isset($candidates[$cursor])) {
                $map[] = $candidates[$cursor];
                $cursor++;

                continue;
            }

            // A rare normalization expansion not understood by the mapper must
            // remain visible but unanchored rather than receive a false offset.
            $map[] = [
                'letter' => $char,
                'source_index' => -1 - count($map),
                'source_byte_start' => -1,
                'source_byte_end' => -1,
                'source_letter' => $char,
            ];
        }

        // ArabicText trims/collapses whitespace. Remove any offset maps that
        // do not match the final normalized form for a predictable ordinal API.
        return $map;
    }

    private function longVowelKind(string $letter, ?string $previousHaraka, bool $hasShadda): ?string
    {
        if ($hasShadda) {
            return null;
        }

        return match (true) {
            in_array($letter, ['ا', 'ى'], true) => 'alif_like',
            $letter === 'و' && $previousHaraka === 'ُ' => 'waw_madd',
            $letter === 'ي' && $previousHaraka === 'ِ' => 'yaa_madd',
            default => null,
        };
    }

    /** @return list<string> */
    private function marksAfterSourceIndex(int $sourceIndex): array
    {
        if ($sourceIndex < 0) {
            return [];
        }

        $marks = [];
        $count = count($this->sourceChars);
        for ($i = $sourceIndex + 1; $i < $count; $i++) {
            $char = $this->sourceChars[$i];
            if (in_array($char, ArabicLetters::INVISIBLE, true)) {
                continue;
            }
            if (ArabicLetters::isHaraka($char)) {
                $marks[] = $char;

                continue;
            }
            break;
        }

        return $marks;
    }

    /** @return array{mark:?string,name:?string,class:?string} */
    private function emptyHaraka(): array
    {
        return ['mark' => null, 'name' => null, 'class' => null];
    }
}
