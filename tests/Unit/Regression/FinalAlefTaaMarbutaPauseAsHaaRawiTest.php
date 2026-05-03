<?php

declare(strict_types=1);

use Mosab\QafyaDetector\QafyaDetector;

it('treats unvocalized taa marbuta after alef as haa rawi with alef radf', function (): void {
    $detector = QafyaDetector::make();

    foreach (['نجاة', 'جفاة', 'عماة', 'الحياة', 'الوشاة', 'شاة'] as $word) {
        $result = $detector->extract($word);

        expect($result->rawi())->toBe('ه', "word={$word}")
            ->and($result->radf())->toBe('ا', "word={$word}")
            ->and($result->wasl())->toBeNull("word={$word}")
            ->and($result->patternSurface())->toBe('اه', "word={$word}");
    }
});

it('keeps visibly vocalized taa marbuta as taa rawi', function (): void {
    $detector = QafyaDetector::make();

    foreach (['الصلاةِ', 'النَجاةِ', 'الحياةِ', 'الوشاةِ'] as $word) {
        $result = $detector->extract($word);

        expect($result->rawi())->toBe('ت', "word={$word}")
            ->and($result->radf())->toBe('ا', "word={$word}")
            ->and($result->wasl())->toBeNull("word={$word}")
            ->and($result->patternSurface())->toBe('ات', "word={$word}");
    }
});

it('does not split paused taa marbuta after alef from explicit aah endings', function (): void {
    $analysis = QafyaDetector::make()->analyze([
        'لَهفي عَلى ساكِنِ شَطِّ الفُراتِ',
        'مروجيه على الحياة',
        'ما تنقضي من عجب فكرتي',
        'من خصلة فرط فيها الولاة',
        'ترك المحبين بلا حاكم',
        'لم يقعدوا للعاشقين القضاة',
        'وقد أتاني خبر ساءني',
        'مقالها في السر واسوءتاه',
    ]);

    expect($analysis->rawi())->toBe('ه')
        ->and($analysis->dominant)->not->toBeNull()
        ->and($analysis->dominant?->radf)->toBe('ا')
        ->and($analysis->qafyaPattern())->toBe('اه')
        ->and($analysis->hasMultipleQafyas())->toBeFalse();
});

it('keeps aaha endings as haa rawi with alef radf and alef wasl', function (): void {
    $analysis = QafyaDetector::make()->analyze([
        'حَوَت ضِدّينِ إِذ ضَرَبَت وَغَنَّت',
        'فَقَد ساءَت وَسُرَّت مَن رَآها',
        'غِناءٌ تَستَحِقُّ عَليهِ ضَرباً',
        'وَضَرباً تَستَحِقُّ بِهِ غِناها',
    ]);

    expect($analysis->rawi())->toBe('ه')
        ->and($analysis->dominant)->not->toBeNull()
        ->and($analysis->dominant?->radf)->toBe('ا')
        ->and($analysis->dominant?->wasl)->toBe('ا')
        ->and($analysis->qafyaPattern())->toBe('اها')
        ->and($analysis->hasMultipleQafyas())->toBeFalse();
});
