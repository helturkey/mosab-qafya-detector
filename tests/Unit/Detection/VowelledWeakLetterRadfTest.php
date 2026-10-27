<?php

declare(strict_types=1);

it('does not treat a vowelled waw or yaa before the rawi as radf', function (): void {
    expect(qafya_detector()->extract('هَوَس')->radf())->toBeNull();
});

it('still treats an unmarked or sakin waw and yaa before the rawi as radf', function (): void {
    expect(qafya_detector()->extract('نُورْ')->radf())->toBe('و')
        ->and(qafya_detector()->extract('بَيْتْ')->radf())->toBe('ي');
});
