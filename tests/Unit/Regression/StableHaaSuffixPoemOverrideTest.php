<?php

declare(strict_types=1);

use Mosab\QafyaDetector\QafyaDetector;

it('resolves stable haa-alef pronoun suffix to the pre-haa rawi', function (): void {
    $analysis = QafyaDetector::make()->analyze([
        'أَلا رُبَّ دَوِيَّةٍ خُضتُها',
        'وَقَد قَيَّدَ العَينَ دَيجورُها',
        'وَحاجَةُ رُمحي ذِيالُها',
        'وَهَمُّ جَوادِيَ يَعفورُها',
        'رَبَأتُ بِها في ذُرى قُلَّةٍ',
        'قَريبٍ مِنَ النَجمِ دَيجورُها',
        'كَأَنَّ السَماءَ بِها لامَةٌ',
        'وَزُهرُ النُجومِ مَساميرُها',
    ]);

    expect($analysis->rawi())->toBe('ر')
        ->and($analysis->dominant?->wasl)->toBe('ه')
        ->and($analysis->dominant?->khurooj)->toBe('ا')
        ->and($analysis->qafyaPattern())->toBe('رها')
        ->and($analysis->hasMultipleQafyas())->toBeFalse();
});

it('preserves stable radf before haa-alef pronoun suffix', function (): void {
    $analysis = QafyaDetector::make()->analyze([
        'غالى بِها الزائِدُ حَتّى اِبتاعَها',
        'بادِنَةً قَد مَلَأَت أَنساعَها',
        'سَوَّغَها الراعي رَبيعَ ضارِجٍ',
        'وَالأَرضُ قَد عَمَّ النَدى بِقاعَها',
        'يورِدُها بَينَ نِطاعٍ فَالنَقا',
        'زُرقَ جِمامٍ لَبِسَت يراعَها',
    ]);

    expect($analysis->rawi())->toBe('ع')
        ->and($analysis->dominant?->radf)->toBe('ا')
        ->and($analysis->dominant?->wasl)->toBe('ه')
        ->and($analysis->dominant?->khurooj)->toBe('ا')
        ->and($analysis->qafyaPattern())->toBe('اعها')
        ->and($analysis->hasMultipleQafyas())->toBeFalse();
});

it('resolves stable yaa-haa suffix poems to yaa rawi', function (): void {
    $analysis = QafyaDetector::make()->analyze([
        'يا طالِباً مُلكَ بَنِي بُوَيهِ',
        'ما أَنتَ مِن ذاكَ وَلا إِلَيهِ',
        'إِرثُ قِوامِ الدينِ عَن أَبَيهِ',
        'خَلِّ عِنانَ المُلكِ في يَدَيهِ',
        'مُناضِلاً يَذُبُّ عَن ثَغرَيهِ',
        'بَديهَةَ الصِلِّ جَلا نابَيهِ',
    ]);

    expect($analysis->rawi())->toBe('ي')
        ->and($analysis->dominant?->wasl)->toBe('ه')
        ->and($analysis->qafyaPattern())->toBe('يه')
        ->and($analysis->hasMultipleQafyas())->toBeFalse();
});
