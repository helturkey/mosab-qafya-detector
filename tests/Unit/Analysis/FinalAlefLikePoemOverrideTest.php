<?php

declare(strict_types=1);

it('promotes final alef-like endings to the poem rawi when the pre-final letters are diverse', function (): void {
    $poem = [
        'ليت ربي يلهمك صدق الشعور',
        'وليت مره تفهميني يا هدى',
        'يا طيوفن صارت بفكري تدور',
        'أسأليني عن شعوري يا غلا',
        'وش حياة اللي فقد م العين نور',
        'تاه به وقته وفكره به سرى',
        'وكل همس من خيالاته تثور',
        'وكل حزنه في عيونه ينجرى',
        'ليه تنسي وتغاضي بهالفتور',
        'ليه تقسي فالصداجه يا ترى',
        'ليت وقت ن منقضي مره يدور',
        'وترجع أيام المعزة للورى',
        'كل غصن ن هايفن عوده حبور',
        'ليه ما نسقي المعزه وتزهرا',
        'بسألك يا بهجة أيامي وعطور',
        'يارفيجة كل دربي وش جرى؟؟',
        'صارت افعالك مثل طفل ن غيور',
        'عجب ما كانت مثل اسد الشرى',
        'كل خصله كانت لقلبي سرور',
        'راعتك من حيث تبديلك سرى',
        'ارجعي لأيام عهدك يا غرور',
        'للأسيره كان ودك ياهلا',
    ];

    $analysis = qafya_detector()->analyze($poem);

    expect($analysis->rawi())->toBe('ى')
        ->and($analysis->qafyaPattern())->toBe('ى')
        ->and($analysis->dominantRatio())->toBe(1.0)
        ->and($analysis->defects())->toBe([]);
});

it('keeps radf when poem-level stable final alef-like override resolves haa rawi', function () {
    $analysis = qafya_detector()->analyze([
        'صدر',
        'فعسى الديار تجيب من ناداها',
        'صدر',
        'والعود والند الذكي جناها',
    ]);

    expect($analysis->rawi())->toBe('ه')
        ->and($analysis->dominant->radf)->toBe('ا')
        ->and($analysis->qafyaPattern())->toBe('اها');
});

it('keeps a visible qafya pattern for single final alef maqsura endings', function (): void {
    $analysis = qafya_detector()->analyze([
        'فَما ماتَ مَن كُنتَ اِبنَهُ لا وَلا الَّذي',
        'لَهُ مِثلُ ما سَدّى أَبوكَ وَما سَعى',
    ]);

    expect($analysis->rawi())->toBe('ى')
        ->and($analysis->qafyaPattern())->toBe('عى')
        ->and($analysis->dominant?->pattern)->toBe('عى');
});
