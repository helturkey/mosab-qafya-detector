<?php

declare(strict_types=1);

use Mosab\QafyaDetector\Analysis\PoemAnalysisPresenter;

it('averages ending confidence and rounds to four decimals', function (): void {
    $presenter = new PoemAnalysisPresenter;

    expect($presenter->averageConfidence([]))->toBe(0.0)
        ->and($presenter->averageConfidence([
            ['result' => ['confidence' => ['score' => 0.9]]],
            ['result' => ['confidence' => ['score' => 0.7]]],
        ]))->toBe(0.8)
        ->and($presenter->averageConfidence([
            ['result' => ['confidence' => ['score' => 0.9]]],
            ['result' => []],
        ]))->toBe(0.45)
        ->and($presenter->averageConfidence([
            ['result' => ['confidence' => ['score' => 1.0]]],
            ['result' => ['confidence' => ['score' => 0.0]]],
            ['result' => ['confidence' => ['score' => 0.0]]],
        ]))->toBe(0.3333);
});

it('builds the dominant payload from a result', function (): void {
    $payload = (new PoemAnalysisPresenter)->dominantPayload([
        'qafya' => [
            'components' => [
                'rawi' => ['letter' => 'ب', 'haraka' => 'ُ'],
                'radf' => ['letter' => 'ا'],
                'wasl' => ['letter' => 'ه'],
            ],
            'motions' => ['mujra' => 'damma'],
        ],
        'segment' => ['surface' => 'ابه'],
        'pattern' => ['surface' => 'ابه'],
        'signatures' => ['strict' => 'Fا-Rب-Wه|mujra=damma'],
    ]);

    expect($payload)->toBe([
        'rawi' => 'ب',
        'rawi_haraka' => 'ُ',
        'mujra' => 'damma',
        'radf' => 'ا',
        'taasis' => null,
        'dakhiil' => null,
        'wasl' => 'ه',
        'khurooj' => null,
        'segment' => 'ابه',
        'pattern' => 'ابه',
        'signature' => 'Fا-Rب-Wه|mujra=damma',
    ]);
});

it('builds an all-null dominant payload from an empty result', function (): void {
    $payload = (new PoemAnalysisPresenter)->dominantPayload([]);

    expect(array_keys($payload))->toBe(['rawi', 'rawi_haraka', 'mujra', 'radf', 'taasis', 'dakhiil', 'wasl', 'khurooj', 'segment', 'pattern', 'signature'])
        ->and(array_filter($payload, static fn (mixed $value): bool => $value !== null))->toBe([]);
});

it('builds the indexing block from the most frequent result', function (): void {
    $indexing = (new PoemAnalysisPresenter)->indexing(
        [
            'qafya' => ['components' => ['rawi' => ['letter' => 'ب', 'haraka' => 'ُ']]],
            'signatures' => ['strict' => 'Rب|mujra=damma'],
            'segment' => ['surface' => 'اب'],
            'pattern' => ['surface' => 'اب'],
        ],
        ['signature' => 'Rب', 'count' => 3, 'ratio' => 0.75],
        0.75,
        false,
    );

    expect($indexing)->toBe([
        'most_frequent_rawi' => 'ب',
        'most_frequent_rawi_haraka' => 'ُ',
        'most_frequent_signature' => 'Rب',
        'most_frequent_strict_signature' => 'Rب|mujra=damma',
        'most_frequent_segment' => 'اب',
        'most_frequent_pattern' => 'اب',
        'most_frequent_count' => 3,
        'most_frequent_ratio' => 0.75,
        'dominant_is_authoritative' => false,
        'usable_for_filtering' => false,
    ]);
});

it('marks an authoritative dominant cluster as usable for filtering', function (): void {
    $indexing = (new PoemAnalysisPresenter)->indexing([], ['signature' => null, 'count' => 0, 'ratio' => 0.0], 1.0, true);

    expect($indexing['dominant_is_authoritative'])->toBeTrue()
        ->and($indexing['usable_for_filtering'])->toBeTrue()
        ->and($indexing['most_frequent_rawi'])->toBeNull();
});
