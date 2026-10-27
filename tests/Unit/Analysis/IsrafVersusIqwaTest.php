<?php

declare(strict_types=1);

it('labels a fatha against damma on the rawi as israf, not iqwa', function (): void {
    $analysis = qafya_detector()->analyze([
        'صدر أول', 'هذا كتابُهُ',
        'صدر ثان', 'هذا حسابَهُ',
    ], ['mode' => 'scholarly']);

    expect($analysis->defectsByType('israf'))->not->toBeEmpty()
        ->and($analysis->defectsByType('iqwa'))->toBeEmpty();
});

it('keeps kasra against damma on the rawi as iqwa', function (): void {
    $analysis = qafya_detector()->analyze([
        'صدر أول', 'هذا كتابُهُ',
        'صدر ثان', 'هذا حسابِهُ',
    ], ['mode' => 'scholarly']);

    expect($analysis->defectsByType('iqwa'))->not->toBeEmpty()
        ->and($analysis->defectsByType('israf'))->toBeEmpty();
});
