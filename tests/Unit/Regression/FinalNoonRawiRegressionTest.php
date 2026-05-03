<?php

declare(strict_types=1);

use Mosab\QafyaDetector\QafyaDetector;

it('keeps ordinary final noon as rawi even with explicit final sukun', function (): void {
    $detector = QafyaDetector::make();

    foreach ([
        'جانحانْ',
        'مِهْرجانْ',
        'المكانْ',
        'شاطئانْ',
        'حصان',
        'كانْ',
        'يدانْ',
        'الزمانْ',
        'الحنانْ',
        'استبيانْ',
    ] as $word) {
        $result = $detector->extract($word);

        expect($result->rawi())->toBe('ن', "word={$word}")
            ->and($result->radf())->toBe('ا', "word={$word}")
            ->and($result->wasl())->toBeNull("word={$word}")
            ->and($result->patternSurface())->toBe('ان', "word={$word}");
    }
});

it('does not split a normal an-ending poem between alef and noon rawis', function (): void {
    $analysis = QafyaDetector::make()->analyze([
        'أطيرُ إليكَ وبي لهفةٌ',
        'ولي من صريحِ الهوى جانحانْ',
        'أزفُّ إليكَ رحالَ المُنى',
        'وفي القلبِ من شوقِهِ مِهْرجانْ',
        'قطعْتُ الطريقَ وأهوالَها',
        'ولكنني ما بلغتُ المكانْ',
        'وخضتُ المحيطَ، فما لاحَ لي',
        'ضياءٌ، أليسَ لهُ شاطئانْ؟',
        'رأيتُ حِصانَ الهوى جامحاً',
        'فأسرجتُ للعقلِ ألفَ حِصان',
        'أقولُ: لقد صارَ رأيُ الفتى',
        'حصيفاً، فكيفَ تقولينَ: كانْ؟',
    ]);

    expect($analysis->rawi())->toBe('ن')
        ->and($analysis->qafyaPattern())->toBe('ان')
        ->and($analysis->hasMultipleQafyas())->toBeFalse();
});

it('still treats explicit emphatic noon as wasl', function (): void {
    $result = QafyaDetector::make()->extract('تَلْعَبَنَّ');

    expect($result->rawi())->toBe('ب')
        ->and($result->wasl())->toBe('ن');
});

it('keeps unvocalized possible noon-niswa as normal noon rawi by default', function (): void {
    $result = QafyaDetector::make()->extract('يكتبن');

    expect($result->rawi())->toBe('ن')
        ->and($result->wasl())->toBeNull();
});

it('only treats clearly vocalized noon-niswa as wasl', function (): void {
    $result = QafyaDetector::make()->extract('يكتبنَ');

    expect($result->rawi())->toBe('ب')
        ->and($result->wasl())->toBe('ن');
});
