<?php

declare(strict_types=1);

use Mosab\QafyaDetector\Data\DominantQafya;
use Mosab\QafyaDetector\Data\PoemReference;
use Mosab\QafyaDetector\Data\PoemSummary;
use Mosab\QafyaDetector\Data\QafyaPattern;
use Mosab\QafyaDetector\Data\QafyaSegment;

it('exposes important word response sections as value objects and accessors', function (): void {
    $result = qafya_detector()->extract('كتابُهُ');

    expect($result->segment)->toBeInstanceOf(QafyaSegment::class)
        ->and($result->pattern)->toBeInstanceOf(QafyaPattern::class)
        ->and($result->segment())->toBe($result->segment->toArray())
        ->and($result->pattern())->toBe($result->pattern->toArray())
        ->and($result->segmentSurface())->not->toBeNull()
        ->and($result->patternSurface())->toBe('ابه')
        ->and($result->strictSignature())->toBeString();
});

it('exposes important poem response sections as value objects and accessors', function (): void {
    $analysis = qafya_detector()->analyze([
        'صدر أول', 'هذا كتابُهُ',
        'صدر ثان', 'هذا حسابُهُ',
    ]);

    expect($analysis->reference)->toBeInstanceOf(PoemReference::class)
        ->and($analysis->summary)->toBeInstanceOf(PoemSummary::class)
        ->and($analysis->dominant)->toBeInstanceOf(DominantQafya::class)
        ->and($analysis->rawi())->toBe('ب')
        ->and($analysis->qafyaSegment())->not->toBeNull()
        ->and($analysis->qafyaPattern())->toBe('ابه')
        ->and($analysis->reference())->toBe($analysis->reference?->toArray())
        ->and($analysis->summary())->toBe($analysis->summary->toArray())
        ->and($analysis->dominant())->toBe($analysis->dominant->toArray());
});
