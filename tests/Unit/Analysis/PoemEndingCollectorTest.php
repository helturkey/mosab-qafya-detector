<?php

declare(strict_types=1);

use Mosab\QafyaDetector\Analysis\PoemEndingCollector;
use Mosab\QafyaDetector\Support\QafyaOptions;

it('collects the ajz ending of every bayt by default', function (): void {
    $collected = (new PoemEndingCollector)->collect(
        ['صدر أول', 'هذا كتابْ', 'صدر ثان', 'هذا حسابْ'],
        QafyaOptions::normalize(),
    );

    expect($collected['unpaired_line_count'])->toBe(0)
        ->and($collected['lines'])->toHaveCount(4)
        ->and($collected['pairs'])->toHaveCount(2)
        ->and($collected['endings'])->toHaveCount(2)
        ->and($collected['endings'][0]['bayt'])->toBe(0)
        ->and($collected['endings'][0]['position'])->toBe('ajz')
        ->and($collected['endings'][0]['text'])->toBe('هذا كتابْ')
        ->and($collected['endings'][0]['matches_reference'])->toBeNull()
        ->and($collected['endings'][0]['violations'])->toBe([])
        ->and($collected['endings'][0]['result']['status'])->toBe('ok')
        ->and($collected['endings'][1]['bayt'])->toBe(1)
        ->and($collected['endings'][1]['text'])->toBe('هذا حسابْ');
});

it('collects both hemistichs when sadr analysis is enabled', function (): void {
    $collected = (new PoemEndingCollector)->collect(
        ['كلامْ', 'سلامْ', 'ظلامْ', 'مقامْ'],
        QafyaOptions::normalize(['analyze_sadr' => true]),
    );

    expect(array_column($collected['endings'], 'position'))->toBe(['sadr', 'ajz', 'sadr', 'ajz'])
        ->and(array_column($collected['endings'], 'bayt'))->toBe([0, 0, 1, 1])
        ->and(array_column($collected['endings'], 'text'))->toBe(['كلامْ', 'سلامْ', 'ظلامْ', 'مقامْ']);
});

it('reports an unpaired trailing hemistich without analyzing it', function (): void {
    $collected = (new PoemEndingCollector)->collect(
        ['صدر أول', 'هذا كتابْ', 'صدر ثان', 'هذا حسابْ', 'صدر ثالث'],
        QafyaOptions::normalize(),
    );

    expect($collected['unpaired_line_count'])->toBe(1)
        ->and($collected['lines'])->toHaveCount(5)
        ->and($collected['pairs'])->toHaveCount(2)
        ->and($collected['endings'])->toHaveCount(2);
});

it('accepts a string poem with mixed separators', function (): void {
    $collected = (new PoemEndingCollector)->collect(
        "صدر أول***هذا كتابْ\nصدر ثان|هذا حسابْ",
        QafyaOptions::normalize(),
    );

    expect($collected['lines'])->toBe(['صدر أول', 'هذا كتابْ', 'صدر ثان', 'هذا حسابْ'])
        ->and($collected['endings'])->toHaveCount(2);
});

it('returns an empty collection for an empty poem', function (): void {
    $collected = (new PoemEndingCollector)->collect([], QafyaOptions::normalize());

    expect($collected)->toBe([
        'lines' => [],
        'pairs' => [],
        'endings' => [],
        'unpaired_line_count' => 0,
    ]);
});

it('treats a single line as an unpaired hemistich', function (): void {
    $collected = (new PoemEndingCollector)->collect(['صدر وحيد'], QafyaOptions::normalize());

    expect($collected['endings'])->toBe([])
        ->and($collected['unpaired_line_count'])->toBe(1);
});
