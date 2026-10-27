<?php

declare(strict_types=1);

use Mosab\QafyaDetector\Support\ArabicText;

it('removes invisible control characters but keeps a non-breaking space as a separator', function (): void {
    expect(ArabicText::removeInvisible("a\u{200B}b\u{00A0}c\u{200F}d\u{FEFF}"))->toBe('ab cd')
        ->and(ArabicText::removeInvisible(''))->toBe('');
});

it('strips vowel marks, tatweel, and dagger alef but keeps letters', function (): void {
    expect(ArabicText::stripTashkeel('كِتَابٌ'))->toBe('كتاب')
        ->and(ArabicText::stripTashkeel('مُحَمَّد'))->toBe('محمد')
        ->and(ArabicText::stripTashkeel("كتــاب\u{0670}"))->toBe('كتاب')
        ->and(ArabicText::stripTashkeel('hello'))->toBe('hello');
});

it('strips non-Arabic characters with or without spaces', function (): void {
    expect(ArabicText::stripNonArabic('abc كتاب 123!'))->toBe(' كتاب ')
        ->and(ArabicText::stripNonArabic('abc كتاب 123!', false))->toBe('كتاب')
        ->and(ArabicText::stripNonArabic('hello'))->toBe('');
});

it('normalizes Arabic text for qafya analysis', function (): void {
    expect(ArabicText::normalizeForQafya('ٱلْكِتَابُ'))->toBe('الكتاب')
        ->and(ArabicText::normalizeForQafya("مُوسَى\u{0670}"))->toBe('موسا')
        ->and(ArabicText::normalizeForQafya("\u{FEE9}"))->toBe('ه')
        ->and(ArabicText::normalizeForQafya('ۀ'))->toBe('ه')
        ->and(ArabicText::normalizeForQafya('  هذا   كتابٌ!  '))->toBe('هذا كتاب')
        ->and(ArabicText::normalizeForQafya("كتاب\u{200F}"))->toBe('كتاب')
        ->and(ArabicText::normalizeForQafya(''))->toBe('')
        ->and(ArabicText::normalizeForQafya('abc'))->toBe('')
        ->and(ArabicText::canonicalEndingWord('كَتَابٌ'))->toBe('كتاب');
});

it('keeps hamza forms distinct during qafya normalization', function (): void {
    expect(ArabicText::normalizeForQafya('أَحْزَانَ'))->toBe('أحزان')
        ->and(ArabicText::normalizeForQafya('سَمَاءٌ'))->toBe('سماء')
        ->and(ArabicText::normalizeForQafya('آهْ'))->toBe('آه');
});

it('normalizes a letter to its rawi identity form', function (): void {
    foreach (['أ', 'إ', 'ؤ', 'ئ', 'ء'] as $hamza) {
        expect(ArabicText::normalizeForRawi($hamza))->toBe('ء');
    }

    expect(ArabicText::normalizeForRawi('ة'))->toBe('ه')
        ->and(ArabicText::normalizeForRawi('ب'))->toBe('ب')
        ->and(ArabicText::normalizeForRawi('ى'))->toBe('ى');
});

it('splits text into characters', function (): void {
    expect(ArabicText::chars('كتاب'))->toBe(['ك', 'ت', 'ا', 'ب'])
        ->and(ArabicText::chars(''))->toBe([]);
});

it('finds the last Arabic word and ignores punctuation and non-Arabic words', function (): void {
    expect(ArabicText::lastArabicWord('قال: «كتابٌ»!', preserveTashkeel: true))->toBe('كتابٌ')
        ->and(ArabicText::lastArabicWord('قال: «كتابٌ»!'))->toBe('كتاب')
        ->and(ArabicText::lastArabicWord('كتابٌ، hello'))->toBe('كتاب')
        ->and(ArabicText::lastArabicWord("كتابٌ\u{200F}", preserveTashkeel: true))->toBe('كتابٌ')
        ->and(ArabicText::lastArabicWord('ناداها…', preserveTashkeel: true))->toBe('ناداها')
        ->and(ArabicText::lastArabicWord('هذا كتابُهُ ؟', preserveTashkeel: true))->toBe('كتابُهُ');
});

it('returns null when there is no Arabic word', function (): void {
    foreach (['', '   ', 'hello', '123 !!', '...'] as $text) {
        expect(ArabicText::lastArabicWord($text))->toBeNull("input={$text}");
    }
});

it('detects meaningful tashkeel, including a lone shadda', function (): void {
    expect(ArabicText::containsMeaningfulTashkeel('كتَاب'))->toBeTrue()
        ->and(ArabicText::containsMeaningfulTashkeel('كتابْ'))->toBeTrue()
        ->and(ArabicText::containsMeaningfulTashkeel('مرّ'))->toBeTrue()
        ->and(ArabicText::containsMeaningfulTashkeel('كتابٌ'))->toBeTrue()
        ->and(ArabicText::containsMeaningfulTashkeel('كتاب'))->toBeFalse()
        ->and(ArabicText::containsMeaningfulTashkeel('كتـاب'))->toBeFalse()
        ->and(ArabicText::containsMeaningfulTashkeel("موسى\u{0670}"))->toBeFalse()
        ->and(ArabicText::containsMeaningfulTashkeel(''))->toBeFalse();
});

it('canonicalizes rawi identity and recognizes hamza seats', function (): void {
    foreach (['ء', 'أ', 'إ', 'ؤ', 'ئ'] as $hamza) {
        expect(ArabicText::canonicalRawiIdentity($hamza))->toBe('ء', $hamza)
            ->and(ArabicText::isHamzaSeat($hamza))->toBeTrue($hamza);
    }

    expect(ArabicText::canonicalRawiIdentity('ب'))->toBe('ب')
        ->and(ArabicText::canonicalRawiIdentity('ا'))->toBe('ا')
        ->and(ArabicText::canonicalRawiIdentity(null))->toBeNull()
        ->and(ArabicText::canonicalRawiIdentity(''))->toBeNull()
        ->and(ArabicText::isHamzaSeat('ا'))->toBeFalse()
        ->and(ArabicText::isHamzaSeat('ب'))->toBeFalse()
        ->and(ArabicText::isHamzaSeat(null))->toBeFalse();
});
