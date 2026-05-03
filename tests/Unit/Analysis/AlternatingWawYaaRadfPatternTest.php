<?php

declare(strict_types=1);

it('omits alternating waw/yaa radf from the public poem-level pattern', function (): void {
    $poem = [
        'بانَ عَهدُ الشَبابِ مِنكُم حَميدا',
        'وَجَديداً لَو كانَ دامَ جَديدا',
        'فَتَرى الظاعِنَ المُقَوِّضُ بَيتَي',
        'هِ يُرجّى مِن قُلعَةٍ أَن يَعودا',
        'لا يُرى ناقِلاً إِلى الحَيِّ رِجلاً',
        'لا وَلا ثانِياً إِلى الدارِ جيدا',
        'فَإِذا شِئتَ أَن تُبَكّي لَيالي',
        'هِ فَمِلأَنَ قُل لِعَينَيكَ جودا',
    ];

    $analysis = qafya_detector()->analyze($poem);

    expect($analysis->rawi())->toBe('د')
        ->and($analysis->dominant)->not->toBeNull()
        ->and($analysis->dominant->radf)->toBeNull()
        ->and($analysis->dominant->wasl)->toBe('ا')
        ->and($analysis->qafyaPattern())->toBe('دا')
        ->and($analysis->dominant->signature)->toBe('Rد-Wا|mujra=?');
});

it('keeps stable alif radf before haa in poem-level pattern', function (): void {
    $poem = [
        'قفْ بالديار وصحْ إلى بيداها',
        'فعسى الديار تجيبُ منْ ناداها',
        'دارٌ يفوحُ المِسْك من عَرَصاتِها',
        'والعودُ والندُّ الذكيُّ جناها',
        'دارٌ لعبلةَ شَطَّ عنْكَ مَزارُها',
        'ونأْتْ لعمْري ما أراكَ تراها',
    ];

    $analysis = qafya_detector()->analyze($poem);

    expect($analysis->rawi())->toBe('ه')
        ->and($analysis->dominant)->not->toBeNull()
        ->and($analysis->dominant->radf)->toBe('ا')
        ->and($analysis->dominant->wasl)->toBe('ا')
        ->and($analysis->qafyaPattern())->toBe('اها');
});
