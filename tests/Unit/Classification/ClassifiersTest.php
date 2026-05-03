<?php

declare(strict_types=1);

use Mosab\QafyaDetector\Classification\QafyaNameClassifier;

it('classifies qafya names by movement count between the two sakins', function (): void {
    $classifier = new QafyaNameClassifier;

    expect($classifier->classify(0))->toBe('mutaradif')
        ->and($classifier->classify(1))->toBe('mutawatir')
        ->and($classifier->classify(2))->toBe('mutadarik')
        ->and($classifier->classify(3))->toBe('mutarakib')
        ->and($classifier->classify(4))->toBe('mutakawis')
        ->and($classifier->classify(5))->toBeNull();
});

it('builds signatures that include rawi and haraka-sensitive strict identity', function (): void {
    $result = qafya_detector()->extractArray('كتابُهُ');

    expect($result['signatures'])->toHaveKeys(['cluster', 'component', 'strict', 'visual'])
        ->and($result['signatures']['component'])->toContain('Rب')
        ->and($result['signatures']['strict'])->toContain('Rب')
        ->and($result['signatures']['visual'])->toBe('ابه');
});

it('includes all six qafya motions in the response', function (): void {
    $motions = qafya_detector()->extractArray('كتابُهُ')['qafya']['motions'];

    expect(array_keys($motions))->toContain('rass', 'ishbaa', 'hadhw', 'tawjih', 'mujra', 'nafadh');
});
