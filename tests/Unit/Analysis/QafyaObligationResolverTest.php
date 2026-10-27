<?php

declare(strict_types=1);

use Mosab\QafyaDetector\Analysis\QafyaObligationResolver;

it('turns the reference components into poem obligations', function (): void {
    $obligations = (new QafyaObligationResolver)->resolve([
        'qafya' => [
            'components' => [
                'rawi' => ['letter' => 'ب'],
                'radf' => ['letter' => 'ا'],
                'wasl' => ['letter' => 'ه'],
            ],
            'motions' => ['mujra' => 'damma'],
        ],
    ]);

    expect($obligations)->toEqual([
        'rawi' => ['required' => true, 'letter' => 'ب'],
        'mujra' => ['required' => true, 'value' => 'damma'],
        'radf' => ['required' => true, 'letter' => 'ا', 'family' => 'alif', 'allowed' => ['ا']],
        'taasis' => ['required' => false, 'letter' => null],
        'wasl' => ['required' => true, 'letter' => 'ه'],
        'khurooj' => ['required' => false, 'letter' => null],
    ]);
});

it('allows waw and yaa to alternate for a waw/yaa radf', function (): void {
    foreach (['و', 'ي'] as $radf) {
        $obligations = (new QafyaObligationResolver)->resolve([
            'qafya' => ['components' => ['rawi' => ['letter' => 'م'], 'radf' => ['letter' => $radf]], 'motions' => []],
        ]);

        expect($obligations['radf']['family'])->toBe('waw_yaa')
            ->and($obligations['radf']['allowed'])->toBe(['و', 'ي']);
    }
});

it('treats alef maqsura radf as the alif family with only alif allowed', function (): void {
    $obligations = (new QafyaObligationResolver)->resolve([
        'qafya' => ['components' => ['rawi' => ['letter' => 'م'], 'radf' => ['letter' => 'ى']], 'motions' => []],
    ]);

    expect($obligations['radf']['family'])->toBe('alif')
        ->and($obligations['radf']['allowed'])->toBe(['ا']);
});

it('requires the taasis and khurooj letters when the reference has them', function (): void {
    $obligations = (new QafyaObligationResolver)->resolve([
        'qafya' => [
            'components' => [
                'taasis' => ['letter' => 'ا'],
                'rawi' => ['letter' => 'ء'],
                'wasl' => ['letter' => 'و'],
                'khurooj' => ['letter' => 'ا'],
            ],
            'motions' => ['mujra' => 'fatha'],
        ],
    ]);

    expect($obligations['taasis'])->toBe(['required' => true, 'letter' => 'ا'])
        ->and($obligations['khurooj'])->toBe(['required' => true, 'letter' => 'ا'])
        ->and($obligations['mujra'])->toBe(['required' => true, 'value' => 'fatha'])
        ->and($obligations['radf']['required'])->toBeFalse();
});

it('requires nothing from an empty reference', function (): void {
    $obligations = (new QafyaObligationResolver)->resolve([]);

    expect($obligations['rawi'])->toBe(['required' => false, 'letter' => null])
        ->and($obligations['mujra'])->toBe(['required' => false, 'value' => null])
        ->and($obligations['radf'])->toBe(['required' => false, 'letter' => null, 'family' => null, 'allowed' => []]);
});
