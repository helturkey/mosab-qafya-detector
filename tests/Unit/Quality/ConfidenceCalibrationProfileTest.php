<?php

declare(strict_types=1);

use Mosab\QafyaDetector\Support\ConfidenceCalibrationProfile;

it('does not expose a candidate profile as a calibrated score before independent holdout validation', function (): void {
    $profile = new ConfidenceCalibrationProfile([
        'schema_version' => 1,
        'profile_id' => 'candidate-profile',
        'status' => 'ready_for_holdout_test',
        'fit_split' => 'validation',
        'sample_count' => 100,
        'bins' => [
            ['upper_bound' => 0.5, 'count' => 30, 'accuracy' => 0.4],
            ['upper_bound' => 1.0, 'count' => 70, 'accuracy' => 0.9],
        ],
    ]);

    expect($profile->calibrate(0.3))->toBeNull();
});

it('maps scores only from a sufficiently large profile validated on an independent holdout', function (): void {
    $profile = new ConfidenceCalibrationProfile([
        'schema_version' => 1,
        'profile_id' => 'validated-profile',
        'status' => 'validated_on_holdout',
        'fit_split' => 'validation',
        'sample_count' => 100,
        'source_predictions_sha256' => str_repeat('a', 64),
        'holdout_evaluation' => [
            'test_predictions_sha256' => str_repeat('b', 64),
            'test_sample_count' => 120,
            'coverage' => 0.9,
            'brier_score_on_mapped_test_records' => 0.12,
            'raw_brier_score_on_same_mapped_records' => 0.2,
            'improves_raw_baseline' => true,
        ],
        'bins' => [
            ['lower_bound' => 0.0, 'upper_bound' => 0.35, 'count' => 20, 'accuracy' => 0.4],
            ['lower_bound' => 0.35, 'upper_bound' => 0.70, 'count' => 30, 'accuracy' => 0.7],
            ['lower_bound' => 0.70, 'upper_bound' => 1.0, 'count' => 50, 'accuracy' => 0.9],
        ],
    ]);

    expect($profile->calibrate(0.3))->toBe(0.4)
        ->and($profile->calibrate(0.6))->toBe(0.7)
        ->and($profile->calibrate(0.8))->toBe(0.9)
        ->and($profile->id())->toBe('validated-profile');
});

it('rejects profiles with weak holdout coverage or a worse brier score', function (): void {
    $profile = new ConfidenceCalibrationProfile([
        'schema_version' => 1,
        'status' => 'validated_on_holdout',
        'fit_split' => 'validation',
        'sample_count' => 100,
        'source_predictions_sha256' => str_repeat('a', 64),
        'holdout_evaluation' => [
            'test_predictions_sha256' => str_repeat('b', 64),
            'test_sample_count' => 120,
            'coverage' => 0.4,
            'brier_score_on_mapped_test_records' => 0.25,
            'raw_brier_score_on_same_mapped_records' => 0.2,
            'improves_raw_baseline' => false,
        ],
        'bins' => [['upper_bound' => 1.0, 'count' => 100, 'accuracy' => 0.8]],
    ]);

    expect($profile->calibrate(0.7))->toBeNull();
});

it('rejects non-monotonic or overlapping calibration bins', function (): void {
    $profile = new ConfidenceCalibrationProfile([
        'schema_version' => 1,
        'status' => 'validated_on_holdout',
        'fit_split' => 'validation',
        'sample_count' => 100,
        'source_predictions_sha256' => str_repeat('a', 64),
        'holdout_evaluation' => [
            'test_predictions_sha256' => str_repeat('b', 64),
            'test_sample_count' => 120,
            'coverage' => 0.9,
            'brier_score_on_mapped_test_records' => 0.12,
            'raw_brier_score_on_same_mapped_records' => 0.2,
            'improves_raw_baseline' => true,
        ],
        'bins' => [
            ['lower_bound' => 0.0, 'upper_bound' => 0.4, 'count' => 40, 'accuracy' => 0.6],
            ['lower_bound' => 0.4, 'upper_bound' => 0.7, 'count' => 40, 'accuracy' => 0.5],
            ['lower_bound' => 0.7, 'upper_bound' => 1.0, 'count' => 40, 'accuracy' => 0.9],
        ],
    ]);

    expect($profile->calibrate(0.5))->toBeNull();
});
