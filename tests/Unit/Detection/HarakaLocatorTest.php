<?php

declare(strict_types=1);

use Mosab\QafyaDetector\Detection\HarakaLocator;

it('finds the vowel on the last occurrence of a letter', function (): void {
    $locator = new HarakaLocator;

    expect($locator->lastForLetter('كتابُهُ', 'ه'))->toBe(['mark' => 'ُ', 'name' => 'ضمة', 'class' => 'damma'])
        ->and($locator->lastForLetter('كتابُهُ', 'ب'))->toBe(['mark' => 'ُ', 'name' => 'ضمة', 'class' => 'damma']);
});

it('matches taa marbuta and haa through the rawi identity form', function (): void {
    $locator = new HarakaLocator;

    expect($locator->lastForLetter('مَدْرَسَةٌ', 'ه')['class'])->toBe('damma')
        ->and($locator->lastForLetter('مَدْرَسَةٌ', 'ة')['mark'])->toBe('ٌ');
});

it('matches any hamza seat against a hamza letter', function (): void {
    expect((new HarakaLocator)->lastForLetter('سَمَاءٌ', 'أ')['mark'])->toBe('ٌ');
});

it('returns an empty haraka for a missing letter, null, or empty letter', function (): void {
    $locator = new HarakaLocator;
    $empty = ['mark' => null, 'name' => null, 'class' => null];

    expect($locator->lastForLetter('كتابُهُ', 'ز'))->toBe($empty)
        ->and($locator->lastForLetter('كتابُهُ', null))->toBe($empty)
        ->and($locator->lastForLetter('كتابُهُ', ''))->toBe($empty)
        ->and($locator->lastForLetter('', 'ب'))->toBe($empty);
});

it('looks up the vowel by exact letter position', function (): void {
    $locator = new HarakaLocator;

    expect($locator->atLetterIndex('كتابُهُ', 3)['class'])->toBe('damma')
        ->and($locator->atLetterIndex('كتابُهُ', 4)['class'])->toBe('damma')
        ->and($locator->atLetterIndex('كتابُهُ', 0)['mark'])->toBeNull()
        ->and($locator->atLetterIndex('كتابُهُ', 99)['mark'])->toBeNull()
        ->and($locator->atLetterIndex('كتابُهُ', -1)['mark'])->toBeNull();
});

it('uses the exact position so a repeated letter keeps its own vowel', function (): void {
    $locator = new HarakaLocator;

    // مَدَدٌ: the first dal carries fatha, the last dal carries tanween damma.
    expect($locator->atLetterIndex('مَدَدٌ', 1)['class'])->toBe('fatha')
        ->and($locator->atLetterIndex('مَدَدٌ', 2)['class'])->toBe('damma')
        ->and($locator->lastForLetter('مَدَدٌ', 'د')['class'])->toBe('damma');
});
