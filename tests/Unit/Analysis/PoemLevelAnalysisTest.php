<?php

declare(strict_types=1);

it('marks a uniform radf plus haa-wasl poem as consistent', function (): void {
    $analysis = qafya_detector()->analyze([
        'صدر أول', 'هذا كتابُهُ',
        'صدر ثان', 'هذا حسابُهُ',
    ]);

    expect($analysis->status())->toBe('ok')
        ->and($analysis->isConsistent())->toBeTrue()
        ->and($analysis->dominantRatio())->toBe(1.0)
        ->and($analysis->rawi())->toBe('ب')
        ->and($analysis->dominant->radf)->toBe('ا')
        ->and($analysis->dominant->wasl)->toBe('ه')
        ->and($analysis->qafyaPattern())->toBe('ابه')
        ->and($analysis->hasDefects())->toBeFalse();
});

it('detects rawi mismatch at poem level', function (): void {
    $analysis = qafya_detector()->analyze([
        'صدر أول', 'هذا كتابُهُ',
        'صدر ثان', 'هذا كلامُهُ',
    ]);

    expect($analysis->status())->toBe('review')
        ->and($analysis->hasDefects())->toBeTrue()
        ->and($analysis->defectsByType('rawi_mismatch'))->not->toBeEmpty();
});

it('detects iqwa when rawi movement changes in scholarly text', function (): void {
    $analysis = qafya_detector()->analyze([
        'صدر أول', 'هذا كتابُهُ',
        'صدر ثان', 'هذا حسابِهُ',
    ], ['mode' => 'scholarly']);

    expect($analysis->defectsByType('iqwa'))->not->toBeEmpty();
});

it('can analyze both sadr and ajz when requested', function (): void {
    $analysis = qafya_detector()->analyze([
        'هذا كتابُهُ', 'هذا حسابُهُ',
        'هذا صوابُهُ', 'هذا جوابُهُ',
    ], ['analyze_sadr' => true]);

    expect($analysis->input['analyzed_positions'])->toBe(['sadr', 'ajz'])
        ->and($analysis->endingsCount())->toBe(4);
});

it('does not borrow unstable taasis and dakhiil from the first dominant ending', function (): void {
    $poem = [
        'لا تلعبي - يا بنت - دور مراقبِ',
        'لا تتعبي وتحاسبي وتعاقبي',
        'هذا أنا بنقاوتي وشقاوتي',
        'هذا أنا بمحاسني ومثالبي',
        'متصالحٌ مع كل ما حولي ولا',
        'أحصي الخسائر أو أعدُّ مكاسبي',
        'هذا أنا - أستاذتي - فلتغفري',
        'هفوات طفلٍ شاطرٍ ومشاغبِ',
        'ببساطةٍ وبدون أيّ تكلُّفٍ:',
        'أهواكِ حتى لو كرهتِ تجاربي',
        'تلك التجارب خضتُها في وقتِها',
        'وأظن كان الوقت غير مناسبِ',
        'والآن وقت حديثنا عن حبّنا',
        'هيا تعالي، يا أحبَّ "حبايبي"',
        'قالت: لدَيْك "حبايبٌ" غيري إذَنْ؟!!',
        'فبقيتّ مصدُومًا.. عقدتُ حواجبي',
        'وأجبتُها: والله إنك دائمًا',
        'في القلب، لكن شكّلي لي قالبي',
        'فأنا - كما تدرينَ - شخصٌ مؤمنٌ',
        'بالله، شخصٌ واثقٌ بمواهبي',
        'لكنني وحدي أخوض غمارها',
        'فأنا الذي وحدي وقفتُ بجانبي',
        'وأنا صديقي في الشدائد كلها',
        'وأنا لنفسي كنتُ أفضل صاحبِ',
        'وبرغم أنّي لا أحبُّ بأن تري',
        'أنّي أعدِّدُ في يديك مناقبي',
        'إلَّا بأنّي كنتُ وحدي أُمّةً',
        'وأبَيْتُ إلّا أن أقوم بواجبي',
        'قالت: مصدّقةٌ، وأعلن أنني:',
        'أستاذةٌ شُغِفَت بهذا الطالبِ!!',
    ];

    $analysis = qafya_detector()->analyze($poem);

    expect($analysis->status())->toBe('ok')
        ->and($analysis->rawi())->toBe('ب')
        ->and($analysis->dominant)->not->toBeNull()
        ->and($analysis->dominant->radf)->toBeNull()
        ->and($analysis->dominant->taasis)->toBeNull()
        ->and($analysis->dominant->dakhiil)->toBeNull()
        ->and($analysis->dominant->wasl)->toBe('ي')
        ->and($analysis->dominant->segment)->toBe('بي')
        ->and($analysis->qafyaPattern())->toBe('بي')
        ->and($analysis->dominant->signature)->toBe('Rب-Wي|mujra=?');
});
