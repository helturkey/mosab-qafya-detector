<?php

declare(strict_types=1);

use Mosab\QafyaDetector\Enums\RawiEligibilityReason;
use Mosab\QafyaDetector\QafyaDetector;

it('treats clear final noon of emphasis as wasl, not rawi', function (): void {
    $result = QafyaDetector::make()->extract('لا تلعبَنَّ');

    expect($result->rawi())->toBe('ب')
        ->and($result->wasl())->toBe('ن')
        ->and($result->patternSurface())->toBe('بن');

    $array = $result->toArray();
    expect(array_column($array['trace'], 'rule'))->toContain('extraneous_noon_tawkid');
});

it('keeps ordinary final noon eligible as rawi', function (): void {
    $result = QafyaDetector::make()->extract('وطن');

    expect($result->rawi())->toBe('ن')
        ->and($result->wasl())->toBeNull();
});

it('exposes rawi eligibility labels and descriptions in component payloads', function (): void {
    $array = QafyaDetector::make()->extractArray('كتابُهُ');
    $rawi = $array['qafya']['components']['rawi'];

    expect($rawi['eligibility_reason'])->toBe(RawiEligibilityReason::AcceptedConsonantalRawi->value)
        ->and($rawi['eligibility_label'])->toBe(RawiEligibilityReason::AcceptedConsonantalRawi->label())
        ->and($rawi['eligibility_description'])->toBe(RawiEligibilityReason::AcceptedConsonantalRawi->description());
});
