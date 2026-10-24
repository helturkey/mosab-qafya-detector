<?php

declare(strict_types=1);

use Mosab\QafyaDetector\Data\QafyaSegment;

it('round-trips original source code-point and UTF-8 byte offsets', function (): void {
    $segment = QafyaSegment::fromArray([
        'surface' => 'كة',
        'arudic' => 'كه',
        'method' => 'test_mapping',
        'complete' => true,
        'moving_count_between_sakins' => 1,
        'last_sakin_index' => 3,
        'previous_sakin_index' => 1,
        'start_index' => 0,
        'warnings' => [],
        'source_span' => [
            'start_index' => 5,
            'end_index' => 7,
            'byte_start' => 10,
            'byte_end' => 14,
        ],
    ]);

    expect($segment->sourceSpan)->toBe([
        'start_index' => 5,
        'end_index' => 7,
        'byte_start' => 10,
        'byte_end' => 14,
    ])->and($segment->toArray()['source_span'])->toBe($segment->sourceSpan);
});
