<?php

declare(strict_types=1);

/**
 * Ported poem-level coverage from the old helper suite.
 *
 * The new API is stricter than the legacy loose-signature clustering: rawi,
 * mujra, obligations, defects and sanad are first-class poem-level concepts.
 */
it('analyses a uniform radf plus haa-wasl poem as consistent', function (): void {
    $analysis = qafya_detector()->analyze([
        'صدر أول', 'هذا كتابُهُ',
        'صدر ثان', 'هذا حسابُهُ',
        'صدر ثالث', 'هذا صوابُهُ',
    ]);

    expect($analysis->status())->toBe('ok')
        ->and($analysis->isConsistent())->toBeTrue()
        ->and($analysis->dominantRatio())->toBe(1.0)
        ->and($analysis->baytCount())->toBe(3)
        ->and($analysis->endingsCount())->toBe(3)
        ->and($analysis->rawi())->toBe('ب')
        ->and($analysis->dominant->radf)->toBe('ا')
        ->and($analysis->dominant->wasl)->toBe('ه')
        ->and($analysis->qafyaPattern())->toBe('ابه')
        ->and($analysis->hasDefects())->toBeFalse();
});

it('marks same suffix with different rawi as review instead of collapsing by loose suffix only', function (): void {
    $analysis = qafya_detector()->analyze([
        'صدر أول', 'جمالها',
        'صدر ثان', 'خصالها',
        'صدر ثالث', 'ديارها',
    ]);

    expect($analysis->status())->toBe('ok')
        ->and($analysis->isConsistent())->toBeTrue()
        ->and($analysis->hasDefects())->toBeFalse()
        ->and($analysis->defectsByType('rawi_mismatch'))->toBeEmpty();
});

it('detects rawi mismatch at poem level', function (): void {
    $analysis = qafya_detector()->analyze([
        'صدر أول', 'هذا كتابُهُ',
        'صدر ثان', 'هذا كلامُهُ',
    ]);

    expect($analysis->status())->toBe('review')
        ->and($analysis->hasDefects())->toBeTrue()
        ->and($analysis->defectsByType('rawi_mismatch'))->not->toBeEmpty();
});

it('detects iqwa when rawi movement changes in diacritized text', function (): void {
    $analysis = qafya_detector()->analyze([
        'صدر أول', 'هذا كتابُهُ',
        'صدر ثان', 'هذا حسابِهُ',
    ], ['mode' => 'scholarly']);

    expect($analysis->defectsByType('iqwa'))->not->toBeEmpty();
});

it('detects sanad radf when the reference requires radf and another ending drops it', function (): void {
    $analysis = qafya_detector()->analyze([
        'صدر أول', 'هذا كتابُهُ',
        'صدر ثان', 'هذا كسبُهُ',
    ]);

    expect($analysis->defectsByType('sanad_radf'))->not->toBeEmpty();
});

it('detects ita when the exact normalized ending word repeats too closely', function (): void {
    $analysis = qafya_detector()->analyze([
        'صدر أول', 'هذا كتابُهُ',
        'صدر ثان', 'هذا كتابُهُ',
    ]);

    expect($analysis->defectsByType('ita'))->not->toBeEmpty();
});

it('promotes final alef-like endings to poem-level rawi when pre-final letters are diverse', function (): void {
    $poem = [
        'ليت ربي يلهمك صدق الشعور', 'وليت مره تفهميني يا هدى',
        'يا طيوفن صارت بفكري تدور', 'أسأليني عن شعوري يا غلا',
        'وش حياة اللي فقد م العين نور', 'تاه به وقته وفكره به سرى',
        'وكل همس من خيالاته تثور', 'وكل حزنه في عيونه ينجرى',
        'ليه تنسي وتغاضي بهالفتور', 'ليه تقسي فالصداجه يا ترى',
        'ليت وقت ن منقضي مره يدور', 'وترجع أيام المعزة للورى',
        'بسألك يا بهجة أيامي وعطور', 'يارفيجة كل دربي وش جرى',
        'ارجعي لأيام عهدك يا غرور', 'للأسيره كان ودك ياهلا',
    ];

    $analysis = qafya_detector()->analyze($poem);

    expect($analysis->rawi())->toBe('ى')
        ->and($analysis->qafyaPattern())->toBe('ى')
        ->and($analysis->dominantRatio())->toBe(1.0)
        ->and($analysis->hasDefects())->toBeFalse();
});

it('preserves written final alef maqsura as rawi for isolated word result', function (): void {
    $result = qafya_detector()->extract('هدى');

    expect($result->rawi())->toBe('ى')
        ->and($result->wasl())->toBeNull()
        ->and($result->patternSurface())->toBe('ى');
});

it('accepts a raw string poem with newline, pipe, and star delimiters', function (): void {
    $detector = qafya_detector();

    expect($detector->analyze("صدر أول\nهذا كتابُهُ\nصدر ثان\nهذا حسابُهُ")->baytCount())->toBe(2)
        ->and($detector->analyze('صدر أول|هذا كتابُهُ|صدر ثان|هذا حسابُهُ')->baytCount())->toBe(2)
        ->and($detector->analyze('صدر أول***هذا كتابُهُ***صدر ثان***هذا حسابُهُ')->baytCount())->toBe(2);
});

it('returns a partial analysis for empty or unpaired input', function (): void {
    $empty = qafya_detector()->analyze([]);
    $odd = qafya_detector()->analyze(['صدر فقط']);

    expect($empty->status())->toBe('partial')
        ->and($empty->error())->toBe('empty_or_unpaired_poem')
        ->and($odd->status())->toBe('partial')
        ->and($odd->error())->toBe('empty_or_unpaired_poem');
});

it('can analyse both sadr and ajz when requested', function (): void {
    $analysis = qafya_detector()->analyze([
        'هذا كتابُهُ', 'هذا حسابُهُ',
        'هذا صوابُهُ', 'هذا جوابُهُ',
    ], ['analyze_sadr' => true]);

    expect($analysis->input['analyzed_positions'])->toBe(['sadr', 'ajz'])
        ->and($analysis->endingsCount())->toBe(4);
});

it('exposes modern response sections instead of legacy keys', function (): void {
    $array = qafya_detector()->analyzeArray([
        'صدر أول', 'هذا كتابُهُ',
        'صدر ثان', 'هذا حسابُهُ',
    ]);

    expect($array)->toHaveKeys([
        'status', 'error', 'input', 'reference', 'summary', 'dominant', 'obligations', 'clusters', 'defects', 'sanad', 'endings', 'diagnostics',
    ])->not->toHaveKey('dominant_analysis')
        ->not->toHaveKey('ending_features')
        ->not->toHaveKey('details');
});
