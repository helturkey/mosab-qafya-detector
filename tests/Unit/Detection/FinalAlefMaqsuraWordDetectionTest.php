<?php

declare(strict_types=1);

it('preserves written final alef maqsura as the word-level rawi', function (string $word): void {
    $result = qafya_detector()->extract($word);

    expect($result->rawi())->toBe('ى')
        ->and($result->wasl())->toBeNull()
        ->and($result->qafyaPattern())->toBe('ى')
        ->and($result->patternSurface())->toBe('ى');
})->with([
    'saa' => ['سعى'],
    'rama' => ['رمى'],
    'huda' => ['هدى'],
]);

it('does not collapse final alef maqsura to bare alef in the component signature', function (): void {
    $result = qafya_detector()->extract('سعى');

    expect($result->signatures['component'] ?? null)->toBe('Rى')
        ->and($result->signatures['strict'] ?? null)->toBe('Rى|mujra=?')
        ->and($result->signatures['visual'] ?? null)->toBe('ى');
});

it('keeps bare final alef as wasl in isolated word-level analysis', function (): void {
    $result = qafya_detector()->extract('غلا');

    expect($result->rawi())->toBe('ل')
        ->and($result->wasl())->toBe('ا')
        ->and($result->qafyaPattern())->toBe('لا');
});
