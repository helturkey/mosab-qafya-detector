<?php

declare(strict_types=1);

namespace Mosab\QafyaDetector\Support;

/**
 * Central Arabic letter sets used by the detector.
 */
final class ArabicLetters
{
    public const TASHKEEL = ['َ', 'ً', 'ُ', 'ٌ', 'ِ', 'ٍ', 'ْ', 'ّ', 'ـ', 'ٰ', 'ٓ', 'ٔ', 'ٕ', 'ٱ', 'ٖ', 'ٗ', '٘'];

    public const SHORT_HARAKAT = ['َ', 'ُ', 'ِ'];

    public const TANWEEN = ['ً', 'ٌ', 'ٍ'];

    public const SUKUN = 'ْ';

    public const SHADDA = 'ّ';

    public const MADD = ['ا', 'و', 'ي', 'ى'];

    public const WEAK = ['ا', 'و', 'ي', 'ى'];

    public const HAA = ['ه', 'ة'];

    public const ALEF_LIKE = ['ا', 'أ', 'إ', 'آ', 'ٱ', 'ى'];

    public const HAMZA = ['ء', 'أ', 'إ', 'ؤ', 'ئ'];

    public const INVISIBLE = ["\u{200B}", "\u{200C}", "\u{200D}", "\u{200E}", "\u{200F}", "\u{00A0}", "\u{FEFF}", "\u{202A}", "\u{202B}", "\u{202C}", "\u{202D}", "\u{202E}"];

    public static function isArabicLetter(string $char): bool
    {
        return preg_match('/[\x{0621}-\x{063A}\x{0641}-\x{064A}]/u', $char) === 1;
    }

    public static function isHaraka(string $char): bool
    {
        return in_array($char, ['َ', 'ً', 'ُ', 'ٌ', 'ِ', 'ٍ', 'ْ', 'ّ'], true);
    }

    public static function isShortHaraka(?string $char): bool
    {
        return $char !== null && in_array($char, self::SHORT_HARAKAT, true);
    }

    public static function isSukun(?string $char): bool
    {
        return $char === self::SUKUN;
    }

    public static function isMadd(?string $char): bool
    {
        return $char !== null && in_array($char, self::MADD, true);
    }

    public static function isWeak(?string $char): bool
    {
        return $char !== null && in_array($char, self::WEAK, true);
    }

    public static function isHaa(?string $char): bool
    {
        return $char !== null && in_array($char, self::HAA, true);
    }

    public static function normalizeAlefLike(string $char): string
    {
        return in_array($char, ['أ', 'إ', 'آ', 'ٱ'], true) ? 'ا' : $char;
    }

    public static function normalizeHamza(?string $char): ?string
    {
        if ($char === null) {
            return null;
        }

        return in_array($char, self::HAMZA, true) ? 'ء' : $char;
    }

    public static function harakaName(?string $mark): ?string
    {
        return match ($mark) {
            'َ' => 'فتحة',
            'ُ' => 'ضمة',
            'ِ' => 'كسرة',
            'ْ' => 'سكون',
            'ً' => 'تنوين فتح',
            'ٌ' => 'تنوين ضم',
            'ٍ' => 'تنوين كسر',
            'ّ' => 'شدة',
            default => null,
        };
    }

    public static function harakaClass(?string $mark): ?string
    {
        return match ($mark) {
            'َ', 'ً' => 'fatha',
            'ُ', 'ٌ' => 'damma',
            'ِ', 'ٍ' => 'kasra',
            'ْ' => 'sukun',
            default => null,
        };
    }

    public static function expectedMaddFromHaraka(?string $mark): ?string
    {
        return match ($mark) {
            'َ', 'ً' => 'ا',
            'ُ', 'ٌ' => 'و',
            'ِ', 'ٍ' => 'ي',
            default => null,
        };
    }
}
