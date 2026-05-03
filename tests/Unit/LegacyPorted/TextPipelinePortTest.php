<?php

declare(strict_types=1);

use Mosab\QafyaDetector\Support\ArabicText;
use Mosab\QafyaDetector\Text\QafyaSegmentExtractor;
use Mosab\QafyaDetector\Text\VerseSplitter;

/**
 * Ported coverage from the old App\Helpers\QafyaHelper test suite.
 *
 * This file intentionally tests the new package text pipeline instead of the
 * removed legacy helper methods.
 */
dataset('ported_last_word_cases', [
    'trailing_comma' => ['يا نائماً، هل تستفيقُ؟!', 'تستفيق'],
    'trailing_period' => ['وَيَبقى وَجهُ رَبِّكَ ذو الجَلالِ.', 'الجلال'],
    'trailing_ellipsis' => ['حتى متى والليالي…', 'والليالي'],
    'trailing_tashkeel' => ['قِفا نَبكِ مِن ذِكرى حَبيبٍ وَمَنزِلِ', 'ومنزل'],
    'alef_maqsura' => ['هَذا وَأَشعارُ الهَوى', 'الهوى'],
    'bare_word' => ['مجد', 'مجد'],
    'leading_spaces' => ['   شعر   ', 'شعر'],
    'tashkeel_heavy' => ['بِسْمِ اللَّهِ الرَّحْمَنِ الرَّحِيمِ', 'الرحيم'],
]);

dataset('ported_unicode_noise_words', [
    'tashkeel' => ['كِتَابٌ'],
    'tatweel' => ['كـتاب'],
    'ltr_mark' => ["ك\u{200E}تاب"],
    'rtl_mark' => ["ك\u{200F}تاب"],
    'nbsp' => ["ك\u{00A0}تاب"],
    'zwsp' => ["ك\u{200B}تاب"],
    'zwnj' => ["ك\u{200C}تاب"],
    'zwj' => ["ك\u{200D}تاب"],
]);

it('normalizes qafya text by stripping tashkeel, tatweel, punctuation noise and invisible marks', function (): void {
    expect(ArabicText::normalizeForQafya('فَتْحَةً'))->toBe('فتحة')
        ->and(ArabicText::normalizeForQafya('كـتاب'))->toBe('كتاب')
        ->and(ArabicText::normalizeForQafya("ك\u{200F}تاب"))->toBe('كتاب')
        ->and(ArabicText::normalizeForQafya('يا نائماً،'))->toBe('يا نائما');
});

it('keeps qafya-sensitive letters such as alef maqsura and hamza-bearing letters visible', function (): void {
    expect(ArabicText::normalizeForQafya('رمى'))->toBe('رمى')
        ->and(ArabicText::normalizeForQafya('سعى'))->toBe('سعى')
        ->and(ArabicText::normalizeForQafya('مستوى'))->toBe('مستوى')
        ->and(ArabicText::normalizeForQafya('وجأوا'))->toBe('وجأوا');
});

it('extracts the last Arabic word consistently', function (string $verse, string $expected): void {
    expect(ArabicText::lastArabicWord($verse))->toBe($expected);
})->with('ported_last_word_cases');

it('returns null for empty or punctuation-only endings', function (): void {
    expect(ArabicText::lastArabicWord(''))->toBeNull()
        ->and(ArabicText::lastArabicWord('   '))->toBeNull()
        ->and(ArabicText::lastArabicWord('،؟!'))->toBeNull();
});

it('splits poem strings using newline, pipe, and star delimiters', function (): void {
    $splitter = new VerseSplitter;

    expect($splitter->split("صدر أول\nعجز أول\nصدر ثان\nعجز ثان"))->toHaveCount(4)
        ->and($splitter->split('صدر أول|عجز أول|صدر ثان|عجز ثان'))->toHaveCount(4)
        ->and($splitter->split('صدر أول***عجز أول***صدر ثان***عجز ثان'))->toHaveCount(4)
        ->and($splitter->split(''))->toBe([]);
});

it('pairs hemistichs and ignores an unmatched trailing line', function (): void {
    $splitter = new VerseSplitter;
    $pairs = $splitter->pairHemistichs(['صدر ١', 'عجز ١', 'صدر ٢', 'عجز ٢', 'صدر زائد']);

    expect($pairs)->toHaveCount(2)
        ->and($pairs[0])->toBe(['index' => 0, 'sadr' => 'صدر ١', 'ajz' => 'عجز ١'])
        ->and($pairs[1])->toBe(['index' => 1, 'sadr' => 'صدر ٢', 'ajz' => 'عجز ٢']);
});

it('detects rawi despite unicode noise', function (string $word): void {
    expect(qafya_detector()->extract($word)->rawi())->toBe('ب');
})->with('ported_unicode_noise_words');

it('falls back safely for undiacritized qafya segment extraction', function (): void {
    $segment = (new QafyaSegmentExtractor)->extract('فلا يغر بطيب العيش إنسان');

    expect($segment['method'])->toBe('estimated_last_word_fallback')
        ->and($segment['complete'])->toBeFalse()
        ->and($segment['surface'])->toBe('انسان')
        ->and($segment['warnings'])->toContain('undiacritized_text');
});

it('does not leak a diacritized segment across the whole hemistich', function (): void {
    $segment = (new QafyaSegmentExtractor)->extract('إِذ سَرى مِن بَيتِهِ في الغَلَسِ');

    expect($segment['method'])->toBe('khalil_last_two_sakins')
        ->and($segment['complete'])->toBeTrue()
        ->and($segment['surface'])->toBe('الغلس')
        ->and($segment['surface'])->not->toContain('بيت');
});
