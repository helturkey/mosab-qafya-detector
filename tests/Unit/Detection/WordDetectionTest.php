<?php

declare(strict_types=1);

use Mosab\QafyaDetector\QafyaDetector;

it('detects published edge case words without legacy engine', function (): void {
    $cases = json_decode((string) file_get_contents(__DIR__.'/../../Fixtures/edge-cases/edge-cases.json'), true, flags: JSON_THROW_ON_ERROR);

    foreach ($cases as $case) {
        $result = QafyaDetector::make()->extract($case['word']);
        expect($result->status())->toBe('ok');
        foreach ($case['expected'] as $key => $expected) {
            expect(match ($key) {
                'rawi' => $result->rawi(),
                'radf' => $result->radf(),
                'wasl' => $result->wasl(),
                'khurooj' => $result->khurooj(),
                'taasis' => $result->taasis(),
                'dakhiil' => $result->dakhiil(),
                default => null,
            })->toBe($expected);
        }
    }
});

it('detects ha as wasl in kitabuhu and ha as rawi in yansahu', function (): void {
    $detector = QafyaDetector::make();

    expect($detector->extract('كتابُهُ')->rawi())->toBe('ب')
        ->and($detector->extract('كتابُهُ')->wasl())->toBe('ه')
        ->and($detector->extract('ينساهُ')->rawi())->toBe('ه')
        ->and($detector->extract('ينساهُ')->radf())->toBe('ا')
        ->and($detector->extract('ينساهُ')->wasl())->toBeNull();
});

it('preserves written final alef maqsura as rawi at word level', function (): void {
    $result = QafyaDetector::make()->extract('الصدى');

    expect($result->rawi())->toBe('ى')
        ->and($result->wasl())->toBeNull()
        ->and($result->patternSurface())->toBe('ى');
});
