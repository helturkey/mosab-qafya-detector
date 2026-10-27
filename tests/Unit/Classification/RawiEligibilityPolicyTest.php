<?php

declare(strict_types=1);

use Mosab\QafyaDetector\Classification\RawiEligibilityPolicy;
use Mosab\QafyaDetector\Text\PhoneticText;

it('flags a missing rawi as ineligible', function (): void {
    foreach ([null, ''] as $letter) {
        $assessment = (new RawiEligibilityPolicy)->assess($letter);

        expect($assessment['eligible'])->toBeFalse()
            ->and($assessment['reason'])->toBe('missing_rawi')
            ->and($assessment['may_be_wasl'])->toBeFalse();
    }
});

it('never accepts a letter assessed in the wasl role', function (): void {
    $assessment = (new RawiEligibilityPolicy)->assess('ه', 'wasl');

    expect($assessment['eligible'])->toBeFalse()
        ->and($assessment['reason'])->toBe('letter_detected_as_wasl')
        ->and($assessment['may_be_wasl'])->toBeTrue();
});

it('keeps alef maqsura eligible but flags it for poem context', function (): void {
    $assessment = (new RawiEligibilityPolicy)->assess('ى');

    expect($assessment['eligible'])->toBeTrue()
        ->and($assessment['reason'])->toBe('final_alef_maqsura_needs_poem_context')
        ->and($assessment['may_be_wasl'])->toBeTrue();
});

it('does not accept a bare madd letter without poem context', function (): void {
    foreach (['ا', 'و', 'ي'] as $letter) {
        $assessment = (new RawiEligibilityPolicy)->assess($letter);

        expect($assessment['eligible'])->toBeFalse($letter)
            ->and($assessment['reason'])->toBe('madd_or_liin_needs_poem_context')
            ->and($assessment['may_be_wasl'])->toBeTrue();
    }
});

it('accepts ordinary consonants and haa as rawi', function (): void {
    foreach (['ب', 'م', 'ر', 'ه', 'ء', 'ت'] as $letter) {
        $assessment = (new RawiEligibilityPolicy)->assess($letter);

        expect($assessment['eligible'])->toBeTrue($letter)
            ->and($assessment['reason'])->toBe('accepted_consonantal_rawi')
            ->and($assessment['may_be_wasl'])->toBeFalse();
    }
});

it('always supplies a human-readable reason label and description', function (): void {
    foreach ([null, 'ب', 'ى', 'ا'] as $letter) {
        $assessment = (new RawiEligibilityPolicy)->assess($letter);

        expect($assessment['reason_label'])->toBeString()->not->toBe('')
            ->and($assessment['reason_description'])->toBeString()->not->toBe('');
    }
});

/**
 * @return array<string, mixed>
 */
function resolve_noon_for(string $word): array
{
    $chars = (new PhoneticText($word))->normalizedLetters();

    return (new RawiEligibilityPolicy)->resolveFinalNoon($word, $chars, count($chars) - 1);
}

it('resolves a feminine-plural noon after a sakin lam as extraneous wasl', function (): void {
    $resolution = resolve_noon_for('يَكْتُبْنَ');

    expect($resolution['applies'])->toBeTrue()
        ->and($resolution['rawi_index'])->toBe(3)
        ->and($resolution['wasl'])->toBe('ن')
        ->and($resolution['category'])->toBe('mutlaqa')
        ->and($resolution['rule'])->toBe('extraneous_noon_niswa');
});

it('resolves an emphatic shadda noon as extraneous wasl', function (): void {
    $resolution = resolve_noon_for('تَلْعَبَنَّ');

    expect($resolution['applies'])->toBeTrue()
        ->and($resolution['rawi_index'])->toBe(3)
        ->and($resolution['wasl'])->toBe('ن')
        ->and($resolution['rule'])->toBe('extraneous_noon_tawkid');
});

it('does not resolve a noon that belongs to the word', function (): void {
    foreach (['حَنَانْ', 'أَحْزَانَ', 'يَعْلَمُونَ', 'أَحْسَنَ', 'يكتبن', 'كتابْ'] as $word) {
        expect(resolve_noon_for($word)['applies'])->toBeFalse($word);
    }
});

it('does not resolve a one-letter ending or an index outside the word', function (): void {
    $policy = new RawiEligibilityPolicy;

    expect($policy->resolveFinalNoon('ن', ['ن'], 0)['applies'])->toBeFalse()
        ->and($policy->resolveFinalNoon('كتاب', ['ك', 'ت', 'ا', 'ب'], 9)['applies'])->toBeFalse();
});
