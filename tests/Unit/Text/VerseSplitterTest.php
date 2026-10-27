<?php

declare(strict_types=1);

use Mosab\QafyaDetector\Text\VerseSplitter;

it('splits a string poem on newlines, pipes, and runs of three or more asterisks', function (): void {
    $lines = (new VerseSplitter)->split("صدر أول\nهذا كتابٌ\r\nصدر ثان***هذا حسابٌ|صدر ثالث");

    expect($lines)->toBe(['صدر أول', 'هذا كتابٌ', 'صدر ثان', 'هذا حسابٌ', 'صدر ثالث']);
});

it('treats a repeated separator as one separator and keeps two asterisks as text', function (): void {
    $splitter = new VerseSplitter;

    expect($splitter->split("أول\n\n\nثان"))->toBe(['أول', 'ثان'])
        ->and($splitter->split('أول*****ثان'))->toBe(['أول', 'ثان'])
        ->and($splitter->split('أول**ثان'))->toBe(['أول**ثان'])
        ->and($splitter->split('أول||ثان'))->toBe(['أول', 'ثان']);
});

it('trims lines and drops empty ones for both string and array input', function (): void {
    $splitter = new VerseSplitter;

    expect($splitter->split("  صدر  \n   \n  عجز  "))->toBe(['صدر', 'عجز'])
        ->and($splitter->split(['  صدر  ', '', '   ', 'عجز']))->toBe(['صدر', 'عجز'])
        ->and($splitter->split(''))->toBe([])
        ->and($splitter->split([]))->toBe([])
        ->and($splitter->split("\n\n"))->toBe([]);
});

it('re-indexes array input after filtering', function (): void {
    expect(array_keys((new VerseSplitter)->split(['a' => 'صدر', 'b' => '', 'c' => 'عجز'])))->toBe([0, 1]);
});

it('pairs hemistichs into numbered bayts and ignores a trailing odd line', function (): void {
    $pairs = (new VerseSplitter)->pairHemistichs(['صدر 1', 'عجز 1', 'صدر 2', 'عجز 2', 'صدر 3']);

    expect($pairs)->toBe([
        ['index' => 0, 'sadr' => 'صدر 1', 'ajz' => 'عجز 1'],
        ['index' => 1, 'sadr' => 'صدر 2', 'ajz' => 'عجز 2'],
    ]);
});

it('pairs nothing for zero or one line', function (): void {
    $splitter = new VerseSplitter;

    expect($splitter->pairHemistichs([]))->toBe([])
        ->and($splitter->pairHemistichs(['صدر']))->toBe([]);
});
