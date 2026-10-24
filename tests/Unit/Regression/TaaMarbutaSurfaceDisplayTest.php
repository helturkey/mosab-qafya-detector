<?php

declare(strict_types=1);

use Mosab\QafyaDetector\QafyaDetector;

it('preserves the written taa marbuta in the visible pattern without changing qafya roles', function (): void {
    $cases = [
        'الهلكة' => ['surface' => 'كة', 'radf' => null, 'signature' => 'Rك-Wه'],
        'البركة' => ['surface' => 'كة', 'radf' => null, 'signature' => 'Rك-Wه'],
        'الحركة' => ['surface' => 'كة', 'radf' => null, 'signature' => 'Rك-Wه'],
        'الشبكة' => ['surface' => 'كة', 'radf' => null, 'signature' => 'Rك-Wه'],
        'منتهكة' => ['surface' => 'كة', 'radf' => null, 'signature' => 'Rك-Wه'],
        'منبتكة' => ['surface' => 'كة', 'radf' => null, 'signature' => 'Rك-Wه'],
        // The yaa immediately before kaf is radf, so this surface is يكة.
        'الديكة' => ['surface' => 'يكة', 'radf' => 'ي', 'signature' => 'Fي-Rك-Wه'],
        'التركة' => ['surface' => 'كة', 'radf' => null, 'signature' => 'Rك-Wه'],
        'الملكة' => ['surface' => 'كة', 'radf' => null, 'signature' => 'Rك-Wه'],
    ];

    $detector = QafyaDetector::make();

    foreach ($cases as $word => $expected) {
        $result = $detector->extract($word);

        expect($result->rawi(), "word={$word}")->toBe('ك')
            ->and($result->radf())->toBe($expected['radf'])
            ->and($result->wasl())->toBe('ه')
            ->and($result->patternSurface())->toBe($expected['surface'])
            ->and($result->visualSignature())->toBe($expected['surface'])
            ->and($result->componentSignature())->toBe($expected['signature']);
    }
});

it('preserves the taa marbuta spelling in the poem dominant pattern', function (): void {
    $endings = ['الهلكة', 'البركة', 'الحركة', 'الشبكة', 'منتهكة', 'منبتكة', 'الديكة', 'التركة', 'الملكة'];
    $lines = [];

    foreach ($endings as $index => $ending) {
        $lines[] = 'صدر البيت '.($index + 1);
        $lines[] = $ending;
    }

    $analysis = QafyaDetector::make()->analyze($lines);

    expect($analysis->rawi())->toBe('ك')
        ->and($analysis->qafyaPattern())->toBe('كة')
        ->and($analysis->dominant?->wasl)->toBe('ه')
        ->and($analysis->hasMultipleQafyas())->toBeFalse();

    foreach ($analysis->endings as $ending) {
        $text = $ending['text'] ?? '';
        $expected = $text === 'الديكة' ? 'يكة' : 'كة';

        expect($ending['result']['pattern']['surface'] ?? null)->toBe($expected);
    }
});

it('does not rewrite a visibly pronounced taa marbuta as paused haa', function (): void {
    $result = QafyaDetector::make()->extract('الصلاةِ');

    expect($result->rawi())->toBe('ت')
        ->and($result->wasl())->toBeNull()
        ->and($result->patternSurface())->toBe('ات');
});
