<?php

declare(strict_types=1);

use Mosab\QafyaDetector\Data\PoemReference;
use Mosab\QafyaDetector\Data\QafyaPattern;
use Mosab\QafyaDetector\Data\QafyaSegment;

it('builds a safe default segment from an empty array', function (): void {
    $segment = QafyaSegment::fromArray([]);

    expect($segment->surface)->toBe('')
        ->and($segment->arudic)->toBe('')
        ->and($segment->method)->toBe('unknown')
        ->and($segment->complete)->toBeFalse()
        ->and($segment->movingCountBetweenSakins)->toBeNull()
        ->and($segment->lastSakinIndex)->toBeNull()
        ->and($segment->previousSakinIndex)->toBeNull()
        ->and($segment->startIndex)->toBeNull()
        ->and($segment->warnings)->toBe([])
        ->and($segment->sourceSpan)->toBeNull();
});

it('coerces numeric strings and drops non-string warnings in a segment', function (): void {
    $segment = QafyaSegment::fromArray([
        'surface' => 'ابه',
        'method' => 'khalil_last_two_sakins',
        'complete' => 1,
        'moving_count_between_sakins' => '3',
        'last_sakin_index' => 5,
        'previous_sakin_index' => 'x',
        'warnings' => ['a', 1, 'b', null],
        'source_span' => ['start_index' => 1, 'end_index' => '4', 'byte_start' => 2, 'byte_end' => 8],
    ]);

    expect($segment->surface)->toBe('ابه')
        ->and($segment->complete)->toBeTrue()
        ->and($segment->movingCountBetweenSakins)->toBe(3)
        ->and($segment->lastSakinIndex)->toBe(5)
        ->and($segment->previousSakinIndex)->toBeNull()
        ->and($segment->warnings)->toBe(['a', 'b'])
        ->and($segment->sourceSpan)->toBe(['start_index' => 1, 'end_index' => 4, 'byte_start' => 2, 'byte_end' => 8]);
});

it('round-trips a segment through toArray and JSON', function (): void {
    $segment = QafyaSegment::fromArray([
        'surface' => 'ابه',
        'arudic' => 'ابهو',
        'method' => 'khalil_last_two_sakins',
        'complete' => true,
        'moving_count_between_sakins' => 1,
        'last_sakin_index' => 4,
        'previous_sakin_index' => 2,
        'start_index' => 1,
        'warnings' => ['final_pause_sukun_assumed'],
        'source_span' => ['start_index' => 1, 'end_index' => 4, 'byte_start' => 2, 'byte_end' => 8],
    ]);

    expect(QafyaSegment::fromArray($segment->toArray())->toArray())->toBe($segment->toArray())
        ->and(json_encode($segment))->toBe(json_encode($segment->toArray()));
});

it('turns empty pattern strings into nulls', function (): void {
    $pattern = QafyaPattern::fromArray(['surface' => 'ابه', 'component' => '', 'strict' => null, 'cluster' => 'Rب-Wه', 'canonical' => 5]);

    expect($pattern->toArray())->toBe([
        'surface' => 'ابه',
        'component' => null,
        'strict' => null,
        'cluster' => 'Rب-Wه',
        'canonical' => null,
    ])
        ->and(json_encode($pattern))->toBe(json_encode($pattern->toArray()))
        ->and(QafyaPattern::fromArray([])->toArray())->toBe([
            'surface' => null,
            'component' => null,
            'strict' => null,
            'cluster' => null,
            'canonical' => null,
        ]);
});

it('defaults and coerces a poem reference', function (): void {
    $default = PoemReference::fromArray([]);
    $coerced = PoemReference::fromArray(['source' => 'first_valid_ending', 'bayt' => '3', 'position' => 'sadr', 'ending' => 'كتابُهُ', 'signature' => '', 'segment' => 'ابه', 'pattern' => 'ابه']);

    expect($default->toArray())->toBe([
        'source' => 'unknown',
        'bayt' => 0,
        'position' => 'ajz',
        'ending' => '',
        'signature' => null,
        'segment' => null,
        'pattern' => null,
    ])
        ->and($coerced->bayt)->toBe(3)
        ->and($coerced->position)->toBe('sadr')
        ->and($coerced->signature)->toBeNull()
        ->and(PoemReference::fromArray($coerced->toArray())->toArray())->toBe($coerced->toArray())
        ->and(json_encode($coerced))->toBe(json_encode($coerced->toArray()));
});
