<?php

declare(strict_types=1);

it('returns modern word response shape with qafya segment and pattern duplicated at stable top-level keys', function (): void {
    $response = qafya_detector()->extractArray('كتابُهُ');

    expect($response)->toHaveKeys(['status', 'input', 'segment', 'qafya', 'signatures', 'pattern', 'confidence', 'diagnostics', 'trace'])
        ->and($response)->not->toHaveKeys(['dominant_analysis', 'ending_features', 'details'])
        ->and($response['qafya'])->toHaveKeys(['category', 'subtype', 'name', 'segment', 'boundary', 'pattern', 'components', 'motions'])
        ->and($response['qafya']['components'])->toHaveKeys(['taasis', 'dakhiil', 'radf', 'rawi', 'wasl', 'khurooj'])
        ->and($response['segment'])->toBe($response['qafya']['segment'])
        ->and($response['pattern'])->toBe($response['qafya']['pattern']);
});

it('returns modern poem response shape with dominant segment and pattern', function (): void {
    $analysis = qafya_detector()->analyzeArray([
        'صدر أول', 'هذا كتابُهُ',
        'صدر ثان', 'هذا حسابُهُ',
    ]);

    expect($analysis)->toHaveKeys(['status', 'error', 'input', 'reference', 'summary', 'dominant', 'obligations', 'clusters', 'defects', 'sanad', 'endings', 'diagnostics'])
        ->and($analysis)->not->toHaveKeys(['dominant_analysis', 'ending_features', 'details'])
        ->and($analysis['reference'])->toHaveKeys(['source', 'bayt', 'position', 'ending', 'signature', 'segment', 'pattern'])
        ->and($analysis['summary'])->toHaveKeys(['qafya_segment', 'qafya_pattern', 'is_consistent', 'dominant_ratio'])
        ->and($analysis['dominant'])->toHaveKeys(['rawi', 'segment', 'pattern', 'signature'])
        ->and($analysis['dominant']['rawi'])->toBe('ب')
        ->and($analysis['dominant']['pattern'])->toBe('ابه');
});
