<?php

declare(strict_types=1);

use Mosab\QafyaDetector\Support\QafyaSurfaceFormatter;

it('restores a written taa marbuta in a paused haa surface', function (): void {
    expect(QafyaSurfaceFormatter::preserveTaaMarbuta('اه', 'نجاة', 'ه', 'ا', null))->toBe('اة')
        ->and(QafyaSurfaceFormatter::preserveTaaMarbuta('اه', 'النَّجَاةُ', 'ه', 'ا', null))->toBe('اة')
        ->and(QafyaSurfaceFormatter::preserveTaaMarbuta('سه', 'مدرسة', 'س', null, 'ه'))->toBe('سة');
});

it('keeps the established aya surface with a haa even for a taa marbuta word', function (): void {
    expect(QafyaSurfaceFormatter::preserveTaaMarbuta('ايه', 'الرواية', 'ي', 'ا', 'ه'))->toBe('ايه');
});

it('leaves a pattern alone when the word does not end in taa marbuta', function (): void {
    expect(QafyaSurfaceFormatter::preserveTaaMarbuta('ابه', 'كتابه', 'ب', 'ا', 'ه'))->toBe('ابه');
});

it('leaves a pattern alone when it does not end in haa', function (): void {
    expect(QafyaSurfaceFormatter::preserveTaaMarbuta('ات', 'الصلاة', 'ت', 'ا', null))->toBe('ات');
});

it('leaves an empty pattern or a missing word unchanged', function (): void {
    expect(QafyaSurfaceFormatter::preserveTaaMarbuta('', 'نجاة'))->toBe('')
        ->and(QafyaSurfaceFormatter::preserveTaaMarbuta('اه', null))->toBe('اه')
        ->and(QafyaSurfaceFormatter::preserveTaaMarbuta('اه', ''))->toBe('اه')
        ->and(QafyaSurfaceFormatter::preserveTaaMarbuta('اه', 123))->toBe('اه');
});
