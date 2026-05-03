<?php

declare(strict_types=1);

use Mosab\QafyaDetector\Data\DominantQafya;
use Mosab\QafyaDetector\Data\PoemQafyaAnalysis;
use Mosab\QafyaDetector\Data\PoemReference;
use Mosab\QafyaDetector\Data\PoemSummary;
use Mosab\QafyaDetector\Data\QafyaPattern;
use Mosab\QafyaDetector\Data\QafyaResult;
use Mosab\QafyaDetector\Data\QafyaSegment;
use Mosab\QafyaDetector\QafyaDetector;

it('exposes a PHP-first object API and JSON-ready array API', function (): void {
    $detector = QafyaDetector::make();

    $word = $detector->extract('كتابُهُ');
    $poem = $detector->analyze([
        'صدر أول', 'هذا كتابُهُ',
        'صدر ثان', 'هذا حسابُهُ',
    ]);

    expect($word)->toBeInstanceOf(QafyaResult::class)
        ->and($word->segment)->toBeInstanceOf(QafyaSegment::class)
        ->and($word->pattern)->toBeInstanceOf(QafyaPattern::class)
        ->and($poem)->toBeInstanceOf(PoemQafyaAnalysis::class)
        ->and($poem->reference)->toBeInstanceOf(PoemReference::class)
        ->and($poem->summary)->toBeInstanceOf(PoemSummary::class)
        ->and($poem->dominant)->toBeInstanceOf(DominantQafya::class);

    expect($detector->extractArray('كتابُهُ'))->toHaveKeys([
        'status', 'input', 'segment', 'qafya', 'signatures', 'pattern', 'confidence', 'diagnostics', 'trace',
    ])->not->toHaveKey('dominant_analysis');

    expect($detector->analyzeArray(['صدر', 'كتابُهُ', 'صدر', 'حسابُهُ']))->toHaveKeys([
        'status', 'error', 'input', 'reference', 'summary', 'dominant', 'obligations', 'clusters', 'defects', 'sanad', 'endings', 'diagnostics',
    ])->not->toHaveKey('ending_features');
});

it('serializes DTOs without losing segment or pattern objects', function (): void {
    $result = qafya_detector()->extract('كتابُهُ');
    $array = $result->toArray();

    expect($array['segment']['surface'])->toBe($result->segment->surface)
        ->and($array['pattern']['surface'])->toBe($result->pattern->surface)
        ->and($array['qafya']['segment'])->toBe($array['segment'])
        ->and($array['qafya']['pattern'])->toBe($array['pattern'])
        ->and($result->jsonSerialize())->toBe($array);
});
