<?php

declare(strict_types=1);

use Mosab\QafyaDetector\QafyaDetector;

it('treats visibly vocalized taa marbuta as taa rawi', function (): void {
    $detector = QafyaDetector::make();

    foreach (['الصَلاةِ', 'قَراةِ', 'بُزاةِ', 'العُداةِ', 'السُعاةِ', 'النَجاةِ'] as $word) {
        $result = $detector->extract($word);

        expect($result->rawi())->toBe('ت', "word={$word}")
            ->and($result->radf())->toBe('ا', "word={$word}")
            ->and($result->wasl())->toBeNull("word={$word}")
            ->and($result->patternSurface())->toBe('ات', "word={$word}");
    }
});

it('keeps aaha endings as haa rawi and does not rewrite them as pronoun suffix', function (): void {
    $analysis = QafyaDetector::make()->analyze([
        'حَوَت ضِدّينِ إِذ ضَرَبَت وَغَنَّت',
        'فَقَد ساءَت وَسُرَّت مَن رَآها',
        'غِناءٌ تَستَحِقُّ عَليهِ ضَرباً',
        'وَضَرباً تَستَحِقُّ بِهِ غِناها',
    ]);

    expect($analysis->rawi())->toBe('ه')
        ->and($analysis->dominant?->radf)->toBe('ا')
        ->and($analysis->dominant?->wasl)->toBe('ا')
        ->and($analysis->qafyaPattern())->toBe('اها')
        ->and($analysis->hasMultipleQafyas())->toBeFalse();
});

it('resolves stable haa with final alef or maqsura as suffix after stable rawi', function (): void {
    $analysis = QafyaDetector::make()->analyze([
        'خالَفَتني وَفَعَلَتها',
        'لَكَ في الخِلافِ المُنتَهى',
        'ما كُنتَ تَعجَزُ في خِصا',
        'لٍ غَيرَها فَخَتَمتَها',
        'أَبصَرتَ نَفسَكَ أَصبَحت',
        'مَستورَةً فَهَتَكتَها',
    ]);

    expect($analysis->rawi())->toBe('ت')
        ->and($analysis->dominant?->wasl)->toBe('ه')
        ->and($analysis->dominant?->khurooj)->toBe('ا')
        ->and($analysis->qafyaPattern())->toBe('تها')
        ->and($analysis->hasMultipleQafyas())->toBeFalse();
});

it('keeps final waw after alef as rawi when vocalized or short lexical ending', function (): void {
    $detector = QafyaDetector::make();

    foreach (['ثاوِ', 'واوِ', 'ثاو', 'واو'] as $word) {
        $result = $detector->extract($word);

        expect($result->rawi())->toBe('و', "word={$word}")
            ->and($result->radf())->toBe('ا', "word={$word}")
            ->and($result->wasl())->toBeNull("word={$word}")
            ->and($result->patternSurface())->toBe('او', "word={$word}");
    }
});

it('keeps final yaa after alef as rawi when vocalized or short lexical ending', function (): void {
    $detector = QafyaDetector::make();

    foreach (['نايُ', 'ناي'] as $word) {
        $result = $detector->extract($word);

        expect($result->rawi())->toBe('ي', "word={$word}")
            ->and($result->radf())->toBe('ا', "word={$word}")
            ->and($result->wasl())->toBeNull("word={$word}")
            ->and($result->patternSurface())->toBe('اي', "word={$word}");
    }
});

it('does not break ordinary final madd wasl cases', function (): void {
    $detector = QafyaDetector::make();

    $yadoo = $detector->extract('يدعو');
    $qadi = $detector->extract('قاضي');

    expect($yadoo->rawi())->toBe('ع')
        ->and($yadoo->wasl())->toBe('و')
        ->and($qadi->rawi())->toBe('ض')
        ->and($qadi->wasl())->toBe('ي');
});
