<?php

declare(strict_types=1);

use Mosab\QafyaDetector\QafyaDetector;

it('keeps unvocalized aya and ayh endings in one yaa rawi family', function (): void {
    $detector = QafyaDetector::make();

    foreach (['الزراية', 'الدرايه', 'الرواية', 'الوقايه', 'الحمايه'] as $word) {
        $result = $detector->extract($word);

        expect($result->rawi())->toBe('ي', "word={$word}")
            ->and($result->radf())->toBe('ا', "word={$word}")
            ->and($result->wasl())->toBe('ه', "word={$word}")
            ->and($result->patternSurface())->toBe('ايه', "word={$word}");
    }
});

it('does not split aya and ayh spellings at poem level', function (): void {
    $analysis = QafyaDetector::make()->analyze([
        'يا قوم ماذا تحملون',
        'من الدعابة والزراية',
        'إن تجهلوا معنى الحماية',
        'فاسألوا أهل الدرايه',
        'كبرت إذ لم تحسنوا',
        'في مصر تمثيل الرواية',
        'الله أكبر أستعيذ',
        'به وأسأله الوقايه',
        'لا تظلموا استقلالكم',
        'حسبي أأنتم والحمايه',
    ]);

    expect($analysis->rawi())->toBe('ي')
        ->and($analysis->dominant?->radf)->toBe('ا')
        ->and($analysis->dominant?->wasl)->toBe('ه')
        ->and($analysis->qafyaPattern())->toBe('ايه')
        ->and($analysis->hasMultipleQafyas())->toBeFalse();
});

it('keeps final yaa after alef as rawi in lexical endings', function (): void {
    $detector = QafyaDetector::make();

    foreach (['ناي', 'مولاي', 'صباي', 'مسعاي'] as $word) {
        $result = $detector->extract($word);

        expect($result->rawi())->toBe('ي', "word={$word}")
            ->and($result->radf())->toBe('ا', "word={$word}")
            ->and($result->wasl())->toBeNull("word={$word}")
            ->and($result->patternSurface())->toBe('اي', "word={$word}");
    }
});

it('keeps final waw after alef as rawi in lexical endings', function (): void {
    $detector = QafyaDetector::make();

    foreach (['ثاو', 'واو'] as $word) {
        $result = $detector->extract($word);

        expect($result->rawi())->toBe('و', "word={$word}")
            ->and($result->radf())->toBe('ا', "word={$word}")
            ->and($result->wasl())->toBeNull("word={$word}")
            ->and($result->patternSurface())->toBe('او', "word={$word}");
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

it('promotes final yaa to rawi when final yaa is shared and pre-yaa letters vary', function (): void {
    $poem = [
        'لَقَد وافاكَ ميخائيلُ نَجلٌ',
        'يُحاكي طَلعةَ الصُبحِ البَهِيِّ',
        'بِهِ راقَ الصَفاءُ لَدَيك لَمّا',
        'أَعادَ لَكَ اِسمهُ عَهدُ السَمِيِّ',
        'فَطِب وَليَحى ابراهيمُ دَهراً',
        'لَديك بِحفظِ مَولاهُ العليِّ',
        'يَعيشُ مُؤرِّخاً نَجلاً سَعيداً',
        'وَتَهنأُ مِنهُ بِالثَمرِ الشَهيِّ',
    ];

    $analysis = qafya_detector()->analyze($poem);

    expect($analysis->status())->toBe('ok')
        ->and($analysis->rawi())->toBe('ي')
        ->and($analysis->hasMultipleQafyas())->toBeFalse()
        ->and($analysis->qafyaPattern())->toBe('ي')
        ->and($analysis->dominant)->not->toBeNull()
        ->and($analysis->dominant->rawi)->toBe('ي')
        ->and($analysis->dominant->wasl)->toBeNull()
        ->and($analysis->dominant->khurooj)->toBeNull();
});
