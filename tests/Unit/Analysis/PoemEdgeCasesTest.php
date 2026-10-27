<?php

declare(strict_types=1);

it('reports no valid reference when every ending lacks Arabic text', function (): void {
    $analysis = qafya_detector()->analyze(['first line', 'second line']);

    expect($analysis->status())->toBe('partial')
        ->and($analysis->error())->toBe('no_valid_qafya_reference')
        ->and($analysis->reference)->toBeNull()
        ->and($analysis->rawi())->toBeNull()
        ->and($analysis->endings())->toBe([])
        ->and($analysis->defects())->toBe([])
        ->and($analysis->hasMultipleQafyas())->toBeFalse()
        ->and($analysis->dominantIsAuthoritative())->toBeFalse();
});

it('treats a dominant ratio of exactly 0.8 as authoritative', function (): void {
    $analysis = qafya_detector()->analyze([
        'صدر 1', 'هذا كتابْ',
        'صدر 2', 'هذا حسابْ',
        'صدر 3', 'هذا عذابْ',
        'صدر 4', 'هذا سرابْ',
        'صدر 5', 'هذا سلامْ',
    ]);
    $notes = $analysis->diagnostics['notes'] ?? [];

    expect($analysis->dominantRatio())->toBe(0.8)
        ->and($analysis->dominantIsAuthoritative())->toBeTrue()
        ->and($analysis->rawi())->toBe('ب')
        ->and($notes)->toContain('dominant_cluster_is_authoritative_but_secondary_clusters_exist')
        ->and($notes)->not->toContain('dominant_ratio_below_authoritative_threshold');
});

it('treats a dominant ratio just below 0.8 as non-authoritative', function (): void {
    $analysis = qafya_detector()->analyze([
        'صدر 1', 'هذا كتابْ',
        'صدر 2', 'هذا حسابْ',
        'صدر 3', 'هذا عذابْ',
        'صدر 4', 'هذا سلامْ',
    ]);
    $notes = $analysis->diagnostics['notes'] ?? [];

    expect($analysis->dominantRatio())->toBe(0.75)
        ->and($analysis->dominantIsAuthoritative())->toBeFalse()
        ->and($analysis->rawi())->toBeNull()
        ->and($notes)->toContain('dominant_ratio_below_authoritative_threshold');
});

it('keeps the average confidence of the endings in the summary', function (): void {
    $analysis = qafya_detector()->analyze([
        'صدر 1', 'هذا كتابْ',
        'صدر 2', 'هذا حسابْ',
    ]);

    expect($analysis->summary()['confidence'])->toBeFloat()
        ->and($analysis->summary()['confidence'])->toBeGreaterThan(0.0)
        ->and($analysis->summary()['confidence'])->toBeLessThanOrEqual(1.0);
});

it('currently counts an ending with no detectable rawi as its own cluster', function (): void {
    // Known limitation, pinned so a fix is a deliberate change: an ending that
    // yields no rawi becomes an "unknown" cluster, which makes the poem look
    // like it has multiple qafyas.
    $analysis = qafya_detector()->analyze([
        'صدر 1', 'هذا كتابْ',
        'صدر 2', 'xyz',
    ]);
    $signatures = array_map(static fn (array $cluster): ?string => $cluster['signature'], $analysis->clusters());

    expect($analysis->clusters())->toHaveCount(2)
        ->and($signatures)->toContain('Rب')
        ->and($signatures)->toContain(null)
        ->and($analysis->hasMultipleQafyas())->toBeTrue()
        ->and($analysis->status())->toBe('review');
});
