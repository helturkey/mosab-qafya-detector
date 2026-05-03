<?php

declare(strict_types=1);

use Mosab\QafyaDetector\QafyaDetector;

it('unifies alef madda and alef before haa-alef endings', function (): void {
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

it('reads waw-alef-madda-haa and shafah as haa rawi with alef radf', function (): void {
    $analysis = QafyaDetector::make()->analyze([
        'أحبكَ كيف تدري أن شعري',
        'يقفِّي اللفظَ.. تنهيدًا.. وآهْ',
        'فلا حاءٌ.. ولا باءٌ.. ستقوى',
        'زفيرًا.. لاحَ جمرًا من شفاهْ',
    ]);

    expect($analysis->hasMultipleQafyas())->toBeFalse()
        ->and($analysis->rawi())->toBe('ه')
        ->and($analysis->qafyaPattern())->toBe('اه');
});

it('resolves taa marbuta to taa when poem context has explicit taa rawi', function (): void {
    $analysis = QafyaDetector::make()->analyze([
        'انظر البركة التي تتراءى لمحيا',
        'الرياض كالمرآة',
        'ترخدّاً مثل اللجين تجلى',
        'بعذار من انعكاس النبات',
    ]);

    expect($analysis->hasMultipleQafyas())->toBeFalse()
        ->and($analysis->rawi())->toBe('ت')
        ->and($analysis->qafyaPattern())->toBe('ات');
});

it('resolves taa marbuta with sukun to taa when matched with explicit taa', function (): void {
    $analysis = QafyaDetector::make()->analyze([
        'وجهي الذي ولألف عامٍ صنتُهُ',
        'هل يستجيرُ بنظرةِ المرآةْ؟!',
        'كيف الحياة تكون أقسى قتلةٍ؟!',
        'كيف الحياة لتصطفي من ماتْ؟!',
    ]);

    expect($analysis->hasMultipleQafyas())->toBeFalse()
        ->and($analysis->rawi())->toBe('ت')
        ->and($analysis->qafyaPattern())->toBe('ات');
});

it('treats final yaa as kasra-ishbaa when matched with taa marbuta kasra context', function (): void {
    $analysis = QafyaDetector::make()->analyze([
        'كلماتي ليست تشبهني',
        'وكأني مثل المرآةِ',
        'دومًا ما أعكسُ ما حولي',
        'لكني لم أعكسْ ذاتي!',
    ]);

    expect($analysis->hasMultipleQafyas())->toBeFalse()
        ->and($analysis->rawi())->toBe('ت')
        ->and($analysis->qafyaPattern())->toBe('ات');
});

it('does not treat final alef madda as rawi', function (): void {
    $result = QafyaDetector::make()->extract('ملآ');

    expect($result->rawi())->toBe('ل')
        ->and($result->wasl())->toBe('ا')
        ->and($result->patternSurface())->toBe('ملآ');
});
