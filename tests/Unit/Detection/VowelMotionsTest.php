<?php

declare(strict_types=1);

it('reads rass and ishbaa from the vowels around ta\'sis and dakhiil', function (): void {
    $motions = qafya_detector()->extract('قَاصِدٌ')->motions();

    expect($motions['rass'])->toBe('fatha')
        ->and($motions['ishbaa'])->toBe('kasra')
        ->and($motions['mujra'])->toBe('damma')
        ->and($motions['hadhw'])->toBeNull()
        ->and($motions['tawjih'])->toBeNull()
        ->and($motions['nafadh'])->toBeNull();
});

it('reads hadhw from the letter before the radf', function (): void {
    $motions = qafya_detector()->extract('نَارٌ')->motions();

    expect($motions['hadhw'])->toBe('fatha')
        ->and($motions['mujra'])->toBe('damma')
        ->and($motions['rass'])->toBeNull()
        ->and($motions['ishbaa'])->toBeNull()
        ->and($motions['tawjih'])->toBeNull();
});

it('reads tawjih from the letter before a muqayyad rawi', function (): void {
    $motions = qafya_detector()->extract('ظَلَمْ')->motions();

    expect($motions['tawjih'])->toBe('fatha')
        ->and($motions['hadhw'])->toBeNull()
        ->and($motions['rass'])->toBeNull()
        ->and($motions['ishbaa'])->toBeNull();
});

it('reads nafadh from the vowel of a haa wasl', function (): void {
    $motions = qafya_detector()->extract('كِتَابُهُ')->motions();

    expect($motions['nafadh'])->toBe('damma')
        ->and($motions['hadhw'])->toBe('fatha')
        ->and($motions['tawjih'])->toBeNull();
});

it('never emits placeholder labels as motion values', function (): void {
    $allowed = [null, 'fatha', 'damma', 'kasra', 'sukun'];

    foreach (['قَاصِدٌ', 'نَارٌ', 'ظَلَمْ', 'كِتَابُهُ', 'جَدِيدًا', 'الزَّمَانَ', 'يكتب'] as $word) {
        foreach (qafya_detector()->extract($word)->motions() as $name => $value) {
            expect(in_array($value, $allowed, true))->toBeTrue("word={$word} motion={$name}");
        }
    }
});
