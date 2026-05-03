<?php

declare(strict_types=1);

it('keeps the six classical qafya letters present in every word response', function (): void {
    $components = qafya_detector()->extractArray('كتابُهُ')['qafya']['components'];

    expect($components)->toHaveKeys(['taasis', 'dakhiil', 'radf', 'rawi', 'wasl', 'khurooj']);
});

it('keeps the six classical qafya motions present in every word response', function (): void {
    $motions = qafya_detector()->extractArray('كتابُهُ')['qafya']['motions'];

    expect($motions)->toHaveKeys(['rass', 'ishbaa', 'hadhw', 'tawjih', 'mujra', 'nafadh']);
});

it('keeps poem-level obligations, defects, and sanad sections in the response', function (): void {
    $analysis = qafya_detector()->analyzeArray([
        'صدر أول', 'هذا كتابُهُ',
        'صدر ثان', 'هذا حسابُهُ',
    ]);

    expect($analysis)->toHaveKeys(['obligations', 'defects', 'sanad'])
        ->and($analysis['obligations'])->toBeArray()
        ->and($analysis['defects'])->toBeArray()
        ->and($analysis['sanad'])->toBeArray();
});
