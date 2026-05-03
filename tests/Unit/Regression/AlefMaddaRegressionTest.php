<?php

declare(strict_types=1);

use Mosab\QafyaDetector\QafyaDetector;

it('does not treat alef madda before haa as rawi', function (): void {
    $result = QafyaDetector::make()->extract('رآها');

    expect($result->rawi())->toBe('ه')
        ->and($result->radf())->toBe('ا')
        ->and($result->wasl())->toBe('ا')
        ->and($result->patternSurface())->toBe('اها');
});

it('unifies raa-ha and ghinaa-ha under haa rawi', function (): void {
    $analysis = QafyaDetector::make()->analyze([
        'حَوَت ضِدّينِ إِذ ضَرَبَت وَغَنَّت',
        'فَقَد ساءَت وَسُرَّت مَن رَآها',
        'غِناءٌ تَستَحِقُّ عَليهِ ضَرباً',
        'وَضَرباً تَستَحِقُّ بِهِ غِناها',
    ]);

    expect($analysis->hasMultipleQafyas())->toBeFalse()
        ->and($analysis->rawi())->toBe('ه')
        ->and($analysis->qafyaPattern())->toBe('اها');
});

it('does not treat alef madda before final haa as rawi', function (): void {
    $result = QafyaDetector::make()->extract('مَرْآهُ');

    expect($result->rawi())->toBe('ه')
        ->and($result->radf())->toBe('ا')
        ->and($result->patternSurface())->toBe('اه');
});

it('does not treat final alef madda as rawi', function (): void {
    $result = QafyaDetector::make()->extract('ملآ');

    expect($result->rawi())->toBe('ل')
        ->and($result->wasl())->toBe('ا')
        ->and($result->patternSurface())->toBe('ملآ');
});

it('does not produce alef madda as a poem-level rawi in final haa-alef dialectal endings', function (): void {
    $analysis = QafyaDetector::make()->analyze([
        'يآمحمد لاتعيد الذآكره وارجع معآهآ',
        'والله انه مابقالي غير دمعه محتظنهآ',
        'المفآرق قسمتي واللي حداني قد حدآهآ',
        'مُهرتي رآآآحت وخلّت من بقآيآهآ رسنهآ',
    ]);

    foreach ($analysis->endings as $ending) {
        $rawi = data_get($ending, 'result.qafya.components.rawi.letter');

        expect($rawi)->not->toBe('آ');
    }
});
