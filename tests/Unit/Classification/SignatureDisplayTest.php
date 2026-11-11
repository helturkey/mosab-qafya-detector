<?php

declare(strict_types=1);

use Mosab\QafyaDetector\Classification\QafyaSignatureBuilder;

/**
 * @param  array<string, mixed>  $extra
 * @return array<string, mixed>
 */
function display_components(string $rawi, array $extra = []): array
{
    return $extra + ['rawi' => ['letter' => $rawi]];
}

/**
 * @param  array<string, mixed>  $context
 * @return array{surface: ?string, prefix: ?string, extended: bool, rule: string}
 */
function display_tail(array $components, array $context): array
{
    return (new QafyaSignatureBuilder)->build($components, [], $context)['display']['tail'];
}

it('does not add a display entry without word context', function (): void {
    $signature = (new QafyaSignatureBuilder)->build(display_components('ب'), []);

    expect($signature)->not->toHaveKey('display')
        ->and(array_keys($signature))->toBe(['component', 'strict', 'cluster', 'visual']);
});

it('adds the explicitly moving letter before a plain rawi', function (): void {
    $display = (new QafyaSignatureBuilder)->build(display_components('ب'), [], ['word' => 'ذَهَبْ', 'rawi_index' => 2])['display'];

    expect($display['surface'])->toBe('ب')
        ->and($display['tail'])->toBe(['surface' => 'هب', 'prefix' => 'ه', 'extended' => true, 'rule' => 'moving_letter_before_rawi']);
});

it('works through a shadda earlier in the word', function (): void {
    $tail = display_tail(display_components('ب'), ['word' => 'كَاللَّهَبْ', 'rawi_index' => 5]);

    expect($tail['surface'])->toBe('هب')
        ->and($tail['prefix'])->toBe('ه');
});

it('keeps wasl letters that are part of the visible pattern', function (): void {
    $tail = display_tail(display_components('ب', ['wasl' => ['letter' => 'ه']]), ['word' => 'ذَهَبُهُ', 'rawi_index' => 2]);

    expect($tail['surface'])->toBe('هبه');
});

it('preserves the written taa marbuta in the surface and the tail', function (): void {
    $components = display_components('ك', ['wasl' => ['letter' => 'ه']]);
    $display = (new QafyaSignatureBuilder)->build($components, [], ['word' => 'بَرَكَةْ', 'rawi_index' => 2])['display'];

    expect($display['surface'])->toBe('كة')
        ->and($display['tail']['surface'])->toBe('ركة');
});

it('keeps the aya yaa haa policy surface for radf alef, rawi yaa and wasl haa', function (): void {
    $components = display_components('ي', ['radf' => ['letter' => 'ا'], 'wasl' => ['letter' => 'ه']]);
    $display = (new QafyaSignatureBuilder)->build($components, [], ['word' => 'الرواية', 'rawi_index' => 5])['display'];

    expect($display['surface'])->toBe('ايه')
        ->and($display['tail']['surface'])->toBe('ايه')
        ->and($display['tail']['extended'])->toBeFalse();
});

it('does not add a letter when the ending has radf, taasis or dakhiil', function (string $role, string $rule): void {
    $tail = display_tail(display_components('ب', [$role => ['letter' => 'ا']]), ['word' => 'كِتَابْ', 'rawi_index' => 3]);

    expect($tail)->toBe(['surface' => 'اب', 'prefix' => null, 'extended' => false, 'rule' => $rule]);
})->with([
    'radf' => ['radf', 'ending_has_radf'],
    'taasis' => ['taasis', 'ending_has_taasis'],
    'dakhiil' => ['dakhiil', 'ending_has_dakhiil'],
]);

it('does not add a letter when a custom surface pattern is in use', function (): void {
    $display = (new QafyaSignatureBuilder)->build(
        display_components('ل'),
        [],
        ['word' => 'مُلَآ', 'rawi_index' => 2, 'surface_pattern' => 'ملآ'],
    )['display'];

    expect($display['surface'])->toBe('ملآ')
        ->and($display['tail']['rule'])->toBe('custom_surface_pattern')
        ->and($display['tail']['extended'])->toBeFalse();
});

it('does not guess when the previous letter is unmarked or has sukun', function (string $word): void {
    $tail = display_tail(display_components('ب'), ['word' => $word, 'rawi_index' => 2]);

    expect($tail['surface'])->toBe('ب')
        ->and($tail['extended'])->toBeFalse()
        ->and($tail['rule'])->toBe('previous_letter_not_explicitly_moving');
})->with([
    'undiacritized' => ['ذهب'],
    'sukun before rawi' => ['قَلْبْ'],
]);

it('handles a missing or first-position rawi and an empty word', function (): void {
    expect(display_tail(display_components('ب'), ['word' => 'بَ', 'rawi_index' => 0])['rule'])->toBe('no_letter_before_rawi')
        ->and(display_tail(display_components('ب'), ['word' => 'ذَهَبْ'])['rule'])->toBe('no_letter_before_rawi')
        ->and(display_tail([], ['word' => ''])['rule'])->toBe('no_pattern_surface');
});

it('returns the same display from display() as from build() with context', function (): void {
    $components = display_components('ب');
    $context = ['word' => 'ذَهَبْ', 'rawi_index' => 2];
    $builder = new QafyaSignatureBuilder;

    expect($builder->display($components, $context))->toBe($builder->build($components, [], $context)['display']);
});
