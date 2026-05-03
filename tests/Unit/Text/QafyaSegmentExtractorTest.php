<?php

declare(strict_types=1);

use Mosab\QafyaDetector\Text\QafyaSegmentExtractor;

it('extracts a complete Khalil boundary when diacritics and sakins are present', function (): void {
    $segment = (new QafyaSegmentExtractor)->extract('فَلَا يُغَرَّ بِطِيبِ العَيْشِ إنْسَانْ');

    expect($segment['method'])->toBe('khalil_last_two_sakins')
        ->and($segment['complete'])->toBeTrue()
        ->and($segment['surface'])->not->toBe('')
        ->and($segment['moving_count_between_sakins'])->toBeInt();
});

it('falls back safely for undiacritized text', function (): void {
    $segment = (new QafyaSegmentExtractor)->extract('فلا يغر بطيب العيش إنسان');

    expect($segment['method'])->toBe('estimated_last_word_fallback')
        ->and($segment['complete'])->toBeFalse()
        ->and($segment['surface'])->toBe('انسان')
        ->and($segment['warnings'])->toContain('undiacritized_text');
});

it('does not let a diacritized boundary leak across the whole hemistich', function (): void {
    $segment = (new QafyaSegmentExtractor)->extract('إِذ سَرى مِن بَيتِهِ في الغَلَسِ');

    expect($segment['method'])->toBe('khalil_last_two_sakins')
        ->and($segment['surface'])->toBe('الغلس')
        ->and($segment['surface'])->not->toContain('منبيته')
        ->and($segment['surface'])->not->toContain('رى');
});

it('normalizes hamza-leading fallback words without losing the qafya surface', function (): void {
    $segment = (new QafyaSegmentExtractor)->extract('فلا يغر بطيب العيش إنسان');

    expect($segment['surface'])->toBe('انسان')
        ->and($segment['arudic'])->toBe('انسان');
});
