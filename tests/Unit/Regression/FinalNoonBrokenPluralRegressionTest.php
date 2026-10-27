<?php

declare(strict_types=1);

it('keeps the final noon of broken plurals and long-vowel endings as the rawi', function (): void {
    $detector = qafya_detector();

    foreach (['أَحْزَانَ', 'أَوْطَانَ', 'أَلْوَانَ', 'أَشْجَانَ', 'تِبْيَانَ'] as $word) {
        $result = $detector->extract($word);

        expect($result->rawi())->toBe('ن', "word={$word}")
            ->and($result->radf())->toBe('ا', "word={$word}")
            ->and($result->wasl())->toBeNull("word={$word}");
    }
});

it('keeps the noon of present-tense plural endings as the rawi with a waw radf', function (): void {
    $result = qafya_detector()->extract('يَعْلَمُونَ');

    expect($result->rawi())->toBe('ن')
        ->and($result->radf())->toBe('و')
        ->and($result->wasl())->toBeNull();
});

it('keeps the noon of perfect verbs with a vowelled root letter as the rawi', function (): void {
    foreach (['أَحْسَنَ', 'أَمْكَنَ'] as $word) {
        $result = qafya_detector()->extract($word);

        expect($result->rawi())->toBe('ن', "word={$word}")
            ->and($result->wasl())->toBeNull("word={$word}");
    }
});

it('still resolves a clear feminine-plural noon as wasl after a sakin lam', function (): void {
    foreach (['يَكْتُبْنَ', 'تَذْهَبْنَ'] as $word) {
        $result = qafya_detector()->extract($word);

        expect($result->rawi())->toBe('ب', "word={$word}")
            ->and($result->wasl())->toBe('ن', "word={$word}");
    }
});

it('does not split a poem rhyming on an-endings when broken plurals are vowelled', function (): void {
    $analysis = qafya_detector()->analyze([
        'صدر أول', 'ولا شيءَ يبقى سوى أَحْزَانَ',
        'صدر ثان', 'وما بقيَ في الدارِ إلا أَوْطَانَ',
        'صدر ثالث', 'وكلُّ الذي كانَ مرَّ الزَّمَانَ',
    ]);

    expect($analysis->rawi())->toBe('ن')
        ->and($analysis->hasMultipleQafyas())->toBeFalse();
});
