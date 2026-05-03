<?php

declare(strict_types=1);

use Mosab\QafyaDetector\QafyaDetector;

it('promotes shared final yaa to poem-level rawi when pre-yaa letters vary', function (): void {
    $poem = [
        'نَفَحَت نسمَةُ مَن أَهوى عَليّ',
        'فَغَدا الحُبُّ بِها مِنّي إِليّ',
        'وَلَوَت كُلّي إِلَيها لَيَّةً',
        'طَوَتِ الكَونَ بِها عَنّيَ طَيّ',
        'يا لَها مِن حُسنِ شَمسٍ أَشرَقَت',
        'لَم يَكُن في جَوِّها وَاللَّهِ فيّ',
        'نَسَخَت آيَتُها آيَ السوى',
        'إِذ سَرَت من لُطفِها في كُلِّ شيّ',
        'لَست بِالعَينِ تَراها إِن بَدَت',
        'إِذ غَدَت لِلكُلِّ عَيناً يا أُخَي',
        'كَم لَها مِن نَظرَةٍ قَد أَسكَرَت',
        'جَهرَة أَهل الهَوى مِن كُلِّ حيّ',
        'فَهيَ إِن تَرض عَلى حُبٍّ لَها',
        'تَأتيهِ رُغماً عَلى أَنفِ اللَحيّ',
        'وَإِذا تاهَت عَلى عاشِقِها',
        'لَم يَفِد في وَصلِها وَاللَهِ شيّ',
        'فَلَها الحُكمُ اِنفِراداً في الوَرى',
        'لَم يَكُن مَعَها مِنَ الكَونَينِ رَأي',
    ];

    $analysis = QafyaDetector::make()->analyze($poem);

    expect($analysis->rawi())->toBe('ي')
        ->and($analysis->qafyaPattern())->toBe('ي')
        ->and($analysis->dominantRatio())->toBe(1.0)
        ->and($analysis->isConsistent())->toBeTrue()
        ->and($analysis->hasMultipleQafyas())->toBeFalse()
        ->and($analysis->isMultipleQafya())->toBeFalse();
});

it('promotes final yaa to rawi for bahiyyi samiyyi aliyyi shahiyyi family', function (): void {
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

    $analysis = QafyaDetector::make()->analyze($poem);

    expect($analysis->status())->toBe('ok')
        ->and($analysis->rawi())->toBe('ي')
        ->and($analysis->qafyaPattern())->toBe('ي')
        ->and($analysis->dominantRatio())->toBe(1.0)
        ->and($analysis->hasMultipleQafyas())->toBeFalse()
        ->and($analysis->clusters)->toHaveCount(1)
        ->and($analysis->dominant?->wasl)->toBeNull()
        ->and($analysis->dominant?->khurooj)->toBeNull();
});
