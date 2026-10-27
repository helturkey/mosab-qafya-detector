<?php

declare(strict_types=1);

use Mosab\QafyaDetector\Support\QafyaOptions;

it('uses documented defaults', function (): void {
    $options = QafyaOptions::normalize();

    expect($options->mode)->toBe('auto')
        ->and($options->strictHarakat)->toBeTrue()
        ->and($options->analyzeSadr)->toBeFalse()
        ->and($options->detectDefects)->toBeTrue()
        ->and($options->detectSanad)->toBeTrue()
        ->and($options->useLinguisticEvidence)->toBeFalse()
        ->and($options->confidenceCalibrationProfile)->toBeNull();
});

it('accepts snake_case and camelCase keys', function (): void {
    $snake = QafyaOptions::normalize([
        'mode' => 'scholarly',
        'strict_harakat' => false,
        'analyze_sadr' => true,
        'detect_defects' => false,
        'detect_sanad' => false,
        'use_linguistic_evidence' => true,
    ]);
    $camel = QafyaOptions::normalize([
        'mode' => 'heuristic',
        'strictHarakat' => false,
        'analyzeSadr' => true,
        'detectDefects' => false,
        'detectSanad' => false,
        'useLinguisticEvidence' => true,
    ]);

    expect($snake->mode)->toBe('scholarly')
        ->and($snake->strictHarakat)->toBeFalse()
        ->and($snake->analyzeSadr)->toBeTrue()
        ->and($snake->detectDefects)->toBeFalse()
        ->and($snake->detectSanad)->toBeFalse()
        ->and($snake->useLinguisticEvidence)->toBeTrue()
        ->and($camel->mode)->toBe('heuristic')
        ->and($camel->strictHarakat)->toBeFalse()
        ->and($camel->analyzeSadr)->toBeTrue()
        ->and($camel->detectDefects)->toBeFalse()
        ->and($camel->detectSanad)->toBeFalse()
        ->and($camel->useLinguisticEvidence)->toBeTrue();
});

it('falls back to auto mode for an unknown mode', function (): void {
    expect(QafyaOptions::normalize(['mode' => 'weird'])->mode)->toBe('auto')
        ->and(QafyaOptions::normalize(['mode' => ''])->mode)->toBe('auto');
});

it('returns an existing options object unchanged', function (): void {
    $options = new QafyaOptions(mode: 'scholarly', analyzeSadr: true);

    expect(QafyaOptions::normalize($options))->toBe($options);
});

it('keeps a calibration profile only when it is an array', function (): void {
    $profile = ['id' => 'p1', 'bins' => []];

    expect(QafyaOptions::normalize(['confidence_calibration_profile' => $profile])->confidenceCalibrationProfile)->toBe($profile)
        ->and(QafyaOptions::normalize(['confidenceCalibrationProfile' => $profile])->confidenceCalibrationProfile)->toBe($profile)
        ->and(QafyaOptions::normalize(['confidence_calibration_profile' => 'nope'])->confidenceCalibrationProfile)->toBeNull();
});

it('round-trips through toArray', function (): void {
    $options = new QafyaOptions(mode: 'heuristic', strictHarakat: false, analyzeSadr: true, detectDefects: false, detectSanad: true, useLinguisticEvidence: true, confidenceCalibrationProfile: ['id' => 'p']);

    expect($options->toArray())->toBe([
        'mode' => 'heuristic',
        'strict_harakat' => false,
        'analyze_sadr' => true,
        'detect_defects' => false,
        'detect_sanad' => true,
        'use_linguistic_evidence' => true,
        'confidence_calibration_profile' => ['id' => 'p'],
    ])
        ->and(QafyaOptions::normalize($options->toArray()))->toEqual($options);
});

it('returns an explicit mode unchanged regardless of the text', function (): void {
    expect((new QafyaOptions(mode: 'heuristic'))->resolvedModeFor('هذا كتابُهُ'))->toBe('heuristic')
        ->and((new QafyaOptions(mode: 'scholarly'))->resolvedModeFor('هذا كتابه'))->toBe('scholarly');
});

it('resolves auto mode from the ending, not from earlier marks in the hemistich', function (): void {
    $options = new QafyaOptions;

    expect($options->resolvedModeFor('هذا كتابُهُ'))->toBe('scholarly')
        ->and($options->resolvedModeFor('هذا كتابْ'))->toBe('scholarly')
        ->and($options->resolvedModeFor('هذا مرّ'))->toBe('scholarly')
        ->and($options->resolvedModeFor('هذا كتابه'))->toBe('heuristic')
        ->and($options->resolvedModeFor('قَالَ هَذَا كتابه'))->toBe('heuristic')
        ->and($options->resolvedModeFor('hello'))->toBe('heuristic')
        ->and($options->resolvedModeFor(''))->toBe('heuristic');
});
