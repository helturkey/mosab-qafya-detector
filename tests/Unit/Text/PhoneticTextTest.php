<?php

declare(strict_types=1);

use Mosab\QafyaDetector\Text\PhoneticText;

it('maps shadda-expanded phonetic units to one original written glyph', function (): void {
    $text = new PhoneticText('مُدّ');
    $units = $text->units();

    expect($text->normalizedLetters())->toBe(['م', 'د'])
        ->and(count($units))->toBe(3)
        ->and($units[1]['letter'])->toBe('د')
        ->and($units[2]['letter'])->toBe('د')
        ->and($units[1]['source_index'])->toBe(2)
        ->and($units[2]['source_index'])->toBe(2)
        ->and($units[1]['source_byte_start'])->toBe(4)
        ->and($units[1]['source_byte_end'])->toBe(6);
});

it('keeps written taa marbuta separate from its pausal phonetic value', function (): void {
    $units = (new PhoneticText('البركة'))->units();
    $last = $units[count($units) - 1];

    expect($last['surface_letter'])->toBe('ة')
        ->and($last['letter'])->toBe('ه')
        ->and($last['source_index'])->toBe(5);
});

it('reports partial tashkeel instead of claiming a complete phonetic reading', function (): void {
    $coverage = (new PhoneticText('نَجاة'))->diacriticsCoverage();

    expect($coverage['fully_marked_for_boundary'])->toBeFalse()
        ->and($coverage['unmarked_internal_consonants'])->toBeGreaterThan(0);
});

it('preserves the exact source mapping around invisible direction marks', function (): void {
    $text = new PhoneticText("ا\u{200F}لبركة");

    expect($text->normalizedLetters())->toBe(['ا', 'ل', 'ب', 'ر', 'ك', 'ة'])
        ->and($text->letterMap()[1]['source_index'])->toBe(2)
        ->and($text->letterMap()[1]['source_byte_start'])->toBe(5);
});

it('recognizes orthographic long vowels from their local vowel evidence', function (): void {
    $units = (new PhoneticText('قُول'))->units();

    $coverage = (new PhoneticText('قُول'))->diacriticsCoverage();

    expect($units[1]['letter'])->toBe('و')
        ->and($units[1]['is_long_vowel'])->toBeTrue()
        ->and($units[1]['long_vowel_kind'])->toBe('waw_madd')
        ->and($units[1]['is_sakin'])->toBeTrue()
        ->and($coverage['fully_marked_for_boundary'])->toBeTrue()
        ->and($coverage['unmarked_internal_consonants'])->toBe(0);
});

it('preserves tanween as explicit source-mapped evidence instead of silently dropping it', function (): void {
    $units = (new PhoneticText('كِتابٌ'))->units();
    $last = $units[count($units) - 1];

    expect($last['haraka'])->toBe('ٌ')
        ->and($last['tanween_type'])->toBe('dammatan')
        ->and($last['vocalization_status'])->toBe('explicit_tanween')
        ->and($last['source_index'])->toBe(4)
        ->and($last['is_moving'])->toBeTrue();
});
