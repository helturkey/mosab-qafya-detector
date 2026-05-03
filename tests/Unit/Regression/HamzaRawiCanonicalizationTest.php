<?php

declare(strict_types=1);

use Mosab\QafyaDetector\QafyaDetector;

it('canonicalizes hamza seats as one rawi identity', function (): void {
    $detector = QafyaDetector::make();

    foreach (['مالئا', 'هادئا', 'بادئا', 'شانئا', 'هانئا'] as $word) {
        $result = $detector->extract($word);

        expect($result->rawi())->toBe('ء', "word={$word}")
            ->and($result->wasl())->toBe('ا', "word={$word}")
            ->and($result->patternSurface())->toBe('ئا', "word={$word}");
    }
});

it('treats final maqsura after hamza seat as yaa wasl', function (): void {
    $detector = QafyaDetector::make();

    foreach (['عزائى', 'بسمائى', 'أحشائى', 'بفدائى', 'دائى'] as $word) {
        $result = $detector->extract($word);

        expect($result->rawi())->toBe('ء', "word={$word}")
            ->and($result->radf())->toBe('ا', "word={$word}")
            ->and($result->wasl())->toBe('ي', "word={$word}");
    }
});

it('does not split hamza seat and bare hamza as different rawis', function (): void {
    $analysis = QafyaDetector::make()->analyze([
        'فاض دمعى وعز فيك رثائى',
        'كيف أقوى يا سلوتى وعزائى',
        'داهم الموت يوم نعيك قلبى',
        'رغم بعدى صريع ذاك البلاء',
    ]);

    expect($analysis->rawi())->toBe('ء')
        ->and($analysis->hasMultipleQafyas())->toBeFalse();
});

it('keeps hamza-seat poem consistent without exposing seat as rawi', function (): void {
    $analysis = QafyaDetector::make()->analyze([
        'ذاك الهوى أضحى لقلبي مالكا',
        'ولكل جانحة بجسمي مالئا',
        'فبمهجتي ثوران بركان جوى',
        'وبظاهري شخص تراه هادئا',
        'الغيث جدا في نهاية أمره',
        'ما خلته إحدى المهازل بادئا',
    ]);

    expect($analysis->rawi())->toBe('ء')
        ->and($analysis->hasMultipleQafyas())->toBeFalse()
        ->and($analysis->qafyaPattern())->toBe('ئا');
});
