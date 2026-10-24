<?php

declare(strict_types=1);

use Mosab\QafyaDetector\PoemQafyaDetector;

it('marks an unmatched trailing hemistich as partial instead of silently ignoring it', function (): void {
    $analysis = (new PoemQafyaDetector)->analyzeArray([
        'صدر البيت الأول',
        'كتابُهُ',
        'شطر منفرد في النهاية',
    ]);

    expect($analysis['input']['unpaired_line_count'])->toBe(1)
        ->and($analysis['status'])->toBe('partial')
        ->and($analysis['diagnostics']['notes'])->toContain('unpaired_trailing_hemistich_ignored');
});

it('reports an unmatched-only poem as an unpaired hemistich error', function (): void {
    $analysis = (new PoemQafyaDetector)->analyzeArray(['شطر واحد فقط']);

    expect($analysis['status'])->toBe('partial')
        ->and($analysis['error'])->toBe('empty_or_unpaired_poem')
        ->and($analysis['input']['unpaired_line_count'])->toBe(1)
        ->and($analysis['diagnostics']['notes'])->toContain('unpaired_trailing_hemistich_ignored');
});
