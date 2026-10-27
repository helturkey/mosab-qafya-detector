<?php

declare(strict_types=1);

use Mosab\QafyaDetector\Text\PhoneticText;

it('counts a long vowel with local evidence as marked', function (): void {
    $coverage = (new PhoneticText('يَعُودُ'))->diacriticsCoverage();

    expect($coverage)->toBe([
        'letter_count' => 4,
        'marked_count' => 4,
        'unmarked_internal_consonants' => 0,
        'coverage' => 1.0,
        'fully_marked_for_boundary' => true,
    ]);
});

it('reports unmarked internal consonants in an undiacritized word', function (): void {
    $coverage = (new PhoneticText('كتاب'))->diacriticsCoverage();

    expect($coverage['letter_count'])->toBe(4)
        ->and($coverage['marked_count'])->toBe(1)
        ->and($coverage['unmarked_internal_consonants'])->toBe(2)
        ->and($coverage['coverage'])->toBe(0.3333)
        ->and($coverage['fully_marked_for_boundary'])->toBeFalse();
});

it('does not count an unmarked final letter as an unmarked internal consonant', function (): void {
    $coverage = (new PhoneticText('كَتَب'))->diacriticsCoverage();

    expect($coverage['unmarked_internal_consonants'])->toBe(0)
        ->and($coverage['fully_marked_for_boundary'])->toBeTrue();
});

it('reports empty coverage for text with no Arabic letters', function (): void {
    $coverage = (new PhoneticText('hello'))->diacriticsCoverage();

    expect($coverage)->toBe([
        'letter_count' => 0,
        'marked_count' => 0,
        'unmarked_internal_consonants' => 0,
        'coverage' => 0.0,
        'fully_marked_for_boundary' => false,
    ]);
});

it('reads the vowel after a shadda in either mark order', function (): void {
    $fathaThenShadda = new PhoneticText("مَر\u{064E}\u{0651}");
    $shaddaThenFatha = new PhoneticText("مَر\u{0651}\u{064E}");

    expect($fathaThenShadda->harakaAtLetterIndex(1)['class'])->toBe('fatha')
        ->and($shaddaThenFatha->harakaAtLetterIndex(1)['class'])->toBe('fatha');
});

it('returns an empty haraka outside the letter range', function (): void {
    $text = new PhoneticText('كِتَابٌ');
    $empty = ['mark' => null, 'name' => null, 'class' => null];

    expect($text->harakaAtLetterIndex(-1))->toBe($empty)
        ->and($text->harakaAtLetterIndex(5))->toBe($empty)
        ->and($text->harakaAtLetterIndex(0)['class'])->toBe('kasra');
});

it('exposes a serializable representation with the source length in code points and bytes', function (): void {
    $array = (new PhoneticText('كِتَابٌ'))->toArray();

    expect($array)->toHaveKeys(['source_text', 'normalized', 'letter_map', 'units', 'diacritics_coverage', 'source_length_codepoints', 'source_length_bytes'])
        ->and($array['source_text'])->toBe('كِتَابٌ')
        ->and($array['normalized'])->toBe('كتاب')
        ->and($array['source_length_codepoints'])->toBe(7)
        ->and($array['source_length_bytes'])->toBe(strlen('كِتَابٌ'));
});
