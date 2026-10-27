<?php

declare(strict_types=1);

it('measures defects against the dominant rawi cluster, not the first ending', function (): void {
    $analysis = qafya_detector()->analyze([
        'صدر أول', 'هذا كتابُهُ',
        'صدر ثان', 'وهذا كلامُهُ',
        'صدر ثالث', 'وذاك سلامُهُ',
        'صدر رابع', 'وهذا غلامُهُ',
    ]);

    $mismatches = $analysis->defectsByType('rawi_mismatch');

    expect($analysis->hasMultipleQafyas())->toBeTrue()
        ->and($analysis->status())->toBe('review')
        ->and($mismatches)->toHaveCount(1)
        ->and($mismatches[0]['bayt'])->toBe(0)
        ->and($analysis->reference?->source)->toBe('first_valid_ending_in_dominant_cluster')
        ->and($analysis->reference?->bayt)->toBe(1);
});

it('keeps the first valid ending as reference when it belongs to the dominant cluster', function (): void {
    $analysis = qafya_detector()->analyze([
        'صدر أول', 'هذا كتابُهُ',
        'صدر ثان', 'هذا حسابُهُ',
    ]);

    expect($analysis->reference?->source)->toBe('first_valid_ending')
        ->and($analysis->reference?->bayt)->toBe(0);
});
