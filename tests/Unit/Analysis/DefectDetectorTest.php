<?php

declare(strict_types=1);

it('records ending-level violations next to each affected bayt', function (): void {
    $analysis = qafya_detector()->analyze([
        'صدر أول', 'هذا كتابُهُ',
        'صدر ثان', 'هذا كلامُهُ',
    ]);

    $secondEnding = $analysis->endingAtBayt(1);

    expect($secondEnding)->toBeArray()
        ->and($secondEnding['matches_reference'])->toBeFalse()
        ->and($secondEnding['violations'])->not->toBeEmpty();
});

it('separates sanad-like defects into the sanad list', function (): void {
    $analysis = qafya_detector()->analyze([
        'صدر أول', 'هذا كتابُهُ',
        'صدر ثان', 'هذا كَتْبُهُ',
    ]);

    expect($analysis->sanad())->toBeArray();
});
