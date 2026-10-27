<?php

declare(strict_types=1);

use Mosab\QafyaDetector\Support\ArabicLetters;

it('recognizes Arabic letters and rejects marks, tatweel, digits, and Latin', function (): void {
    foreach (['ب', 'ء', 'أ', 'ة', 'ى', 'ي', 'ه'] as $letter) {
        expect(ArabicLetters::isArabicLetter($letter))->toBeTrue($letter);
    }

    foreach (['a', '1', '١', ' ', 'َ', 'ْ', 'ّ', 'ـ', '،'] as $other) {
        expect(ArabicLetters::isArabicLetter($other))->toBeFalse($other);
    }
});

it('recognizes the eight haraka marks', function (): void {
    foreach (['َ', 'ً', 'ُ', 'ٌ', 'ِ', 'ٍ', 'ْ', 'ّ'] as $mark) {
        expect(ArabicLetters::isHaraka($mark))->toBeTrue($mark);
    }

    expect(ArabicLetters::isHaraka('ب'))->toBeFalse()
        ->and(ArabicLetters::isHaraka('ـ'))->toBeFalse();
});

it('separates short vowels, sukun, madd, weak letters, and haa', function (): void {
    expect(ArabicLetters::isShortHaraka('َ'))->toBeTrue()
        ->and(ArabicLetters::isShortHaraka('ُ'))->toBeTrue()
        ->and(ArabicLetters::isShortHaraka('ِ'))->toBeTrue()
        ->and(ArabicLetters::isShortHaraka('ً'))->toBeFalse()
        ->and(ArabicLetters::isShortHaraka('ْ'))->toBeFalse()
        ->and(ArabicLetters::isShortHaraka(null))->toBeFalse()
        ->and(ArabicLetters::isSukun('ْ'))->toBeTrue()
        ->and(ArabicLetters::isSukun('َ'))->toBeFalse()
        ->and(ArabicLetters::isSukun(null))->toBeFalse();

    foreach (['ا', 'و', 'ي', 'ى'] as $madd) {
        expect(ArabicLetters::isMadd($madd))->toBeTrue($madd)
            ->and(ArabicLetters::isWeak($madd))->toBeTrue($madd);
    }

    expect(ArabicLetters::isMadd('آ'))->toBeFalse()
        ->and(ArabicLetters::isMadd('ب'))->toBeFalse()
        ->and(ArabicLetters::isMadd(null))->toBeFalse()
        ->and(ArabicLetters::isWeak(null))->toBeFalse()
        ->and(ArabicLetters::isHaa('ه'))->toBeTrue()
        ->and(ArabicLetters::isHaa('ة'))->toBeTrue()
        ->and(ArabicLetters::isHaa('ب'))->toBeFalse()
        ->and(ArabicLetters::isHaa(null))->toBeFalse();
});

it('normalizes alef-like and hamza letters', function (): void {
    foreach (['أ', 'إ', 'آ', 'ٱ'] as $alef) {
        expect(ArabicLetters::normalizeAlefLike($alef))->toBe('ا', $alef);
    }

    expect(ArabicLetters::normalizeAlefLike('ا'))->toBe('ا')
        ->and(ArabicLetters::normalizeAlefLike('ى'))->toBe('ى')
        ->and(ArabicLetters::normalizeAlefLike('ب'))->toBe('ب')
        ->and(ArabicLetters::normalizeHamza('أ'))->toBe('ء')
        ->and(ArabicLetters::normalizeHamza('ئ'))->toBe('ء')
        ->and(ArabicLetters::normalizeHamza('ب'))->toBe('ب')
        ->and(ArabicLetters::normalizeHamza(null))->toBeNull();
});

it('names and classifies every haraka', function (): void {
    $expected = [
        'َ' => ['فتحة', 'fatha'],
        'ً' => ['تنوين فتح', 'fatha'],
        'ُ' => ['ضمة', 'damma'],
        'ٌ' => ['تنوين ضم', 'damma'],
        'ِ' => ['كسرة', 'kasra'],
        'ٍ' => ['تنوين كسر', 'kasra'],
        'ْ' => ['سكون', 'sukun'],
    ];

    foreach ($expected as $mark => [$name, $class]) {
        expect(ArabicLetters::harakaName($mark))->toBe($name)
            ->and(ArabicLetters::harakaClass($mark))->toBe($class);
    }

    expect(ArabicLetters::harakaName('ّ'))->toBe('شدة')
        ->and(ArabicLetters::harakaClass('ّ'))->toBeNull()
        ->and(ArabicLetters::harakaName(null))->toBeNull()
        ->and(ArabicLetters::harakaClass(null))->toBeNull()
        ->and(ArabicLetters::harakaName('ب'))->toBeNull();
});

it('maps a vowel to the madd letter it lengthens into', function (): void {
    expect(ArabicLetters::expectedMaddFromHaraka('َ'))->toBe('ا')
        ->and(ArabicLetters::expectedMaddFromHaraka('ً'))->toBe('ا')
        ->and(ArabicLetters::expectedMaddFromHaraka('ُ'))->toBe('و')
        ->and(ArabicLetters::expectedMaddFromHaraka('ٌ'))->toBe('و')
        ->and(ArabicLetters::expectedMaddFromHaraka('ِ'))->toBe('ي')
        ->and(ArabicLetters::expectedMaddFromHaraka('ٍ'))->toBe('ي')
        ->and(ArabicLetters::expectedMaddFromHaraka('ْ'))->toBeNull()
        ->and(ArabicLetters::expectedMaddFromHaraka(null))->toBeNull();
});
