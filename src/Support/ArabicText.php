<?php

declare(strict_types=1);

namespace Mosab\QafyaDetector\Support;

final class ArabicText
{
    public static function removeInvisible(string $text): string
    {
        // Zero-width directional/control marks can appear inside Arabic words
        // after copy/paste. Removing them is safer than converting them to
        // spaces, otherwise "ك\u{200F}تاب" becomes "ك تاب" and the last-word
        // and qafya normalizers see a broken word. Non-breaking spaces remain
        // word separators.
        $text = str_replace("\u{00A0}", ' ', $text);

        return str_replace(array_diff(ArabicLetters::INVISIBLE, ["\u{00A0}"]), '', $text);
    }

    public static function stripTashkeel(string $text): string
    {
        return str_replace(ArabicLetters::TASHKEEL, '', $text);
    }

    public static function stripNonArabic(string $text, bool $keepSpaces = true): string
    {
        $space = $keepSpaces ? '\\s' : '';
        $clean = preg_replace('/[^\x{0621}-\x{063A}\x{0641}-\x{064A}'.$space.']/u', '', $text);

        return $clean ?? '';
    }

    public static function normalizeForQafya(string $text): string
    {
        $text = self::removeInvisible($text);
        $text = str_replace(['ٱ', 'ىٰ', 'ۀ', 'ہ', 'ﻩ', 'ﻪ'], ['ا', 'ا', 'ه', 'ه', 'ه', 'ه'], $text);
        $text = self::stripTashkeel($text);
        $text = self::stripNonArabic($text);
        $text = preg_replace('/\s+/u', ' ', trim($text));

        return $text ?? '';
    }

    public static function normalizeForRawi(string $letter): string
    {
        $letter = ArabicLetters::normalizeHamza($letter) ?? $letter;

        return $letter === 'ة' ? 'ه' : $letter;
    }

    /**
     * @return list<string>
     */
    public static function chars(string $text): array
    {
        return mb_str_split($text) ?: [];
    }

    public static function lastArabicWord(string $text, bool $preserveTashkeel = false): ?string
    {
        $text = self::removeInvisible($text);
        $text = preg_replace('/[\p{P}\p{S}،؛؟!…]+/u', ' ', $text) ?? $text;
        $words = preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY);
        if ($words === false || $words === []) {
            return null;
        }

        for ($i = count($words) - 1; $i >= 0; $i--) {
            $word = $words[$i];
            if (preg_match('/[\x{0621}-\x{063A}\x{0641}-\x{064A}]/u', $word) === 1) {
                return $preserveTashkeel ? $word : self::normalizeForQafya($word);
            }
        }

        return null;
    }

    public static function containsMeaningfulTashkeel(string $text): bool
    {
        return preg_match('/[ًٌٍَُِْ]/u', $text) === 1;
    }

    public static function canonicalEndingWord(string $word): string
    {
        return self::normalizeForQafya($word);
    }

    public static function canonicalRawiIdentity(?string $letter): ?string
    {
        if ($letter === null || $letter === '') {
            return null;
        }

        return match ($letter) {
            'ء', 'أ', 'إ', 'ؤ', 'ئ' => 'ء',
            default => $letter,
        };
    }

    public static function isHamzaSeat(?string $letter): bool
    {
        return in_array($letter, ['ء', 'أ', 'إ', 'ؤ', 'ئ'], true);
    }
}
