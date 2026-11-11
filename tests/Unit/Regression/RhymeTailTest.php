<?php

declare(strict_types=1);

it('returns the shared tail هب from qafyaPattern() for ذَهَبْ and اللَّهَبْ without changing rawi or patternSurface', function (string $ending): void {
    $result = qafya_detector()->extract($ending);

    expect($result->rawi())->toBe('ب')
        ->and($result->radf())->toBeNull()
        ->and($result->patternSurface())->toBe('ب')
        ->and($result->qafyaPattern())->toBe('هب');
})->with([
    'ذَهَبْ' => ['ذَهَبْ'],
    'كَاللَّهَبْ' => ['كَاللَّهَبْ'],
    'hemistich ending in ذَهَبْ' => ['عَلَى الرُّبَى فِي ثَوْبِ عَرُوسٍ مِنْ ذَهَبْ'],
    'hemistich ending in اللَّهَبْ' => ['وَانْطَفَأَ الضِّيَاءُ عَنِ السُّحُبِ كَاللَّهَبْ'],
]);

it('exposes the tail details under qafya.tail', function (): void {
    $array = qafya_detector()->extractArray('ذَهَبْ');

    expect($array['qafya']['tail'])->toBe([
        'surface' => 'هب',
        'prefix' => 'ه',
        'extended' => true,
        'rule' => 'moving_letter_before_rawi',
    ])
        ->and($array['pattern']['surface'])->toBe('ب')
        ->and($array)->toHaveKeys(['status', 'input', 'segment', 'qafya', 'signatures', 'pattern', 'confidence', 'diagnostics', 'trace']);
});

it('keeps the tail equal to the pattern when the ending has a radf', function (): void {
    $result = qafya_detector()->extract('كِتَابْ');

    expect($result->radf())->toBe('ا')
        ->and($result->patternSurface())->toBe('اب')
        ->and($result->qafyaPattern())->toBe('اب');
});

it('does not invent a moving letter for undiacritized endings', function (): void {
    $result = qafya_detector()->extract('ذهب');

    expect($result->patternSurface())->toBe('ب')
        ->and($result->qafyaPattern())->toBe('ب')
        ->and($result->qafya['tail']['extended'])->toBeFalse();
});

it('leaves the component and strict signatures unchanged', function (): void {
    $result = qafya_detector()->extract('ذَهَبْ');

    expect($result->componentSignature())->toBe('Rب')
        ->and($result->visualSignature())->toBe('ب');
});
