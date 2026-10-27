<?php

declare(strict_types=1);

it('reports an exact moving count and name when the ending is fully marked', function (): void {
    $result = qafya_detector()->extract('يَعُودُ');

    expect($result->segment()['moving_count_between_sakins'])->toBe(0)
        ->and($result->name())->toBe('mutaradif');
});

it('leaves the moving count and name unknown when an internal consonant is unmarked', function (): void {
    $result = qafya_detector()->extract('الغَلَسِ');

    expect($result->segment()['moving_count_between_sakins'])->toBeNull()
        ->and($result->name())->toBeNull();
});
