<?php

declare(strict_types=1);

use Mosab\QafyaDetector\Analysis\RhymeDefectDetector;
use Mosab\QafyaDetector\Analysis\SanadDetector;

/**
 * Build a minimal ending the way PoemEndingCollector would hand it over.
 *
 * @param  array<string, string|null>  $components  extra component letters keyed by role
 * @return array<string, mixed>
 */
function defect_ending(int $bayt, ?string $rawi, array $components = [], ?string $mujra = null, ?string $word = null): array
{
    $letters = [];

    foreach (['rawi' => $rawi] + $components as $role => $letter) {
        if ($letter !== null) {
            $letters[$role] = ['letter' => $letter];
        }
    }

    return [
        'bayt' => $bayt,
        'position' => 'ajz',
        'result' => [
            'status' => 'ok',
            'input' => ['normalized' => $word],
            'qafya' => [
                'components' => $letters,
                'motions' => ['mujra' => $mujra],
            ],
        ],
    ];
}

/**
 * @param  list<array<string, mixed>>  $endings
 * @return list<array<string, mixed>>
 */
function detect_defects_for(array $endings): array
{
    return (new RhymeDefectDetector)->detect($endings[0], $endings);
}

/**
 * @param  list<array<string, mixed>>  $defects
 * @return list<string>
 */
function defect_types(array $defects): array
{
    return array_values(array_map(static fn (array $defect): string => (string) $defect['type'], $defects));
}

it('reports no defects when every ending shares the reference components', function (): void {
    $endings = [
        defect_ending(0, 'ب', ['radf' => 'ا'], 'damma', 'كتاب'),
        defect_ending(1, 'ب', ['radf' => 'ا'], 'damma', 'حساب'),
        defect_ending(2, 'ب', ['radf' => 'ا'], 'damma', 'عذاب'),
    ];

    expect(detect_defects_for($endings))->toBe([]);
});

it('reports a rawi mismatch with expected and actual letters', function (): void {
    $defects = detect_defects_for([
        defect_ending(0, 'ب', [], null, 'كتاب'),
        defect_ending(1, 'م', [], null, 'سلام'),
    ]);

    expect($defects)->toHaveCount(1)
        ->and($defects[0])->toBe([
            'type' => 'rawi_mismatch',
            'arabic_name' => 'اختلاف الروي',
            'severity' => 'error',
            'bayt' => 1,
            'expected' => ['rawi' => 'ب'],
            'actual' => ['rawi' => 'م'],
        ]);
});

it('does not compare vowels when the rawi differs', function (): void {
    $defects = detect_defects_for([
        defect_ending(0, 'ب', [], 'damma'),
        defect_ending(1, 'م', [], 'kasra'),
    ]);

    expect(defect_types($defects))->toBe(['rawi_mismatch']);
});

it('classifies kasra against damma as iqwa and fatha against another vowel as israf', function (): void {
    expect(defect_types(detect_defects_for([
        defect_ending(0, 'م', [], 'damma'),
        defect_ending(1, 'م', [], 'kasra'),
    ])))->toBe(['iqwa'])
        ->and(defect_types(detect_defects_for([
            defect_ending(0, 'م', [], 'kasra'),
            defect_ending(1, 'م', [], 'damma'),
        ])))->toBe(['iqwa'])
        ->and(defect_types(detect_defects_for([
            defect_ending(0, 'م', [], 'damma'),
            defect_ending(1, 'م', [], 'fatha'),
        ])))->toBe(['israf'])
        ->and(defect_types(detect_defects_for([
            defect_ending(0, 'م', [], 'fatha'),
            defect_ending(1, 'م', [], 'kasra'),
        ])))->toBe(['israf']);
});

it('carries the vowels in iqwa and israf defects', function (): void {
    $iqwa = detect_defects_for([defect_ending(0, 'م', [], 'damma'), defect_ending(1, 'م', [], 'kasra')])[0];
    $israf = detect_defects_for([defect_ending(0, 'م', [], 'damma'), defect_ending(1, 'م', [], 'fatha')])[0];

    expect($iqwa['arabic_name'])->toBe('الإقواء')
        ->and($iqwa['severity'])->toBe('error')
        ->and($iqwa['expected'])->toBe(['mujra' => 'damma'])
        ->and($iqwa['actual'])->toBe(['mujra' => 'kasra'])
        ->and($israf['arabic_name'])->toBe('الإصراف')
        ->and($israf['severity'])->toBe('error');
});

it('treats sukun or a missing vowel against a vowel as a pausal variant, not a defect', function (): void {
    expect(detect_defects_for([
        defect_ending(0, 'م', [], 'damma'),
        defect_ending(1, 'م', [], 'sukun'),
    ]))->toBe([])
        ->and(detect_defects_for([
            defect_ending(0, 'م', [], 'sukun'),
            defect_ending(1, 'م', [], 'kasra'),
        ]))->toBe([])
        ->and(detect_defects_for([
            defect_ending(0, 'م', [], 'damma'),
            defect_ending(1, 'م', [], null),
        ]))->toBe([]);
});

it('reports sanad of radf when the radf family changes', function (): void {
    $defects = detect_defects_for([
        defect_ending(0, 'م', ['radf' => 'ا']),
        defect_ending(1, 'م', ['radf' => 'و']),
        defect_ending(2, 'م', ['radf' => 'ي']),
    ]);

    expect(defect_types($defects))->toBe(['sanad_radf', 'sanad_radf'])
        ->and($defects[0]['expected'])->toBe(['radf_family' => 'alif'])
        ->and($defects[0]['actual'])->toBe(['radf_family' => 'waw_yaa'])
        ->and(array_column($defects, 'bayt'))->toBe([1, 2]);
});

it('allows waw and yaa radf to alternate', function (): void {
    expect(detect_defects_for([
        defect_ending(0, 'م', ['radf' => 'و']),
        defect_ending(1, 'م', ['radf' => 'ي']),
        defect_ending(2, 'م', ['radf' => 'و']),
    ]))->toBe([]);
});

it('reports sanad of radf when an alif reference meets alef maqsura radf', function (): void {
    $defects = detect_defects_for([
        defect_ending(0, 'م', ['radf' => 'ا']),
        defect_ending(1, 'م', ['radf' => 'ى']),
    ]);

    expect(defect_types($defects))->toBe(['sanad_radf'])
        ->and($defects[0]['expected'])->toBe(['radf' => 'ا'])
        ->and($defects[0]['actual'])->toBe(['radf' => 'ى']);
});

it('reports sanad of radf when an ending drops the radf of the reference', function (): void {
    $defects = detect_defects_for([
        defect_ending(0, 'م', ['radf' => 'و']),
        defect_ending(1, 'م'),
    ]);

    expect(defect_types($defects))->toBe(['sanad_radf'])
        ->and($defects[0]['expected'])->toBe(['radf' => 'و'])
        ->and($defects[0]['actual'])->toBe(['radf' => null]);
});

it('does not report sanad of radf when only a later ending adds a radf', function (): void {
    expect(detect_defects_for([
        defect_ending(0, 'م'),
        defect_ending(1, 'م', ['radf' => 'ا']),
    ]))->toBe([]);
});

it('reports sanad of taasis in both directions', function (): void {
    $missing = detect_defects_for([
        defect_ending(0, 'ب', ['taasis' => 'ا', 'dakhiil' => 'ت']),
        defect_ending(1, 'ب'),
    ]);
    $extra = detect_defects_for([
        defect_ending(0, 'ب'),
        defect_ending(1, 'ب', ['taasis' => 'ا', 'dakhiil' => 'ت']),
    ]);

    expect(defect_types($missing))->toBe(['sanad_taasis'])
        ->and($missing[0]['expected'])->toBe(['taasis' => 'ا'])
        ->and($missing[0]['actual'])->toBe(['taasis' => null])
        ->and(defect_types($extra))->toBe(['sanad_taasis'])
        ->and($extra[0]['expected'])->toBe(['taasis' => null])
        ->and($extra[0]['actual'])->toBe(['taasis' => 'ا']);
});

it('reports wasl and khurooj mismatches as warnings only when both letters exist', function (): void {
    $wasl = detect_defects_for([
        defect_ending(0, 'م', ['wasl' => 'ا']),
        defect_ending(1, 'م', ['wasl' => 'ي']),
    ]);
    $khurooj = detect_defects_for([
        defect_ending(0, 'ه', ['khurooj' => 'ا']),
        defect_ending(1, 'ه', ['khurooj' => 'و']),
    ]);
    $absent = detect_defects_for([
        defect_ending(0, 'م', ['wasl' => 'ا']),
        defect_ending(1, 'م'),
    ]);

    expect(defect_types($wasl))->toBe(['wasl_mismatch'])
        ->and($wasl[0]['severity'])->toBe('warning')
        ->and($wasl[0]['arabic_name'])->toBe('اختلاف الوصل')
        ->and(defect_types($khurooj))->toBe(['khurooj_mismatch'])
        ->and($khurooj[0]['severity'])->toBe('warning')
        ->and($khurooj[0]['arabic_name'])->toBe('اختلاف الخروج')
        ->and($absent)->toBe([]);
});

it('reports ita only when the same word returns within fewer than seven bayts', function (): void {
    $near = detect_defects_for([
        defect_ending(0, 'ب', [], null, 'كتاب'),
        defect_ending(1, 'ب', [], null, 'حساب'),
        defect_ending(6, 'ب', [], null, 'كتاب'),
    ]);
    $far = detect_defects_for([
        defect_ending(0, 'ب', [], null, 'كتاب'),
        defect_ending(7, 'ب', [], null, 'كتاب'),
    ]);

    expect(defect_types($near))->toBe(['ita'])
        ->and($near[0]['severity'])->toBe('warning')
        ->and($near[0]['arabic_name'])->toBe('الإيطاء')
        ->and($near[0]['bayt'])->toBe(6)
        ->and($near[0]['expected'])->toBe(['distance_at_least' => 7])
        ->and($near[0]['actual'])->toBe(['repeated_word' => 'كتاب', 'previous_bayt' => 0])
        ->and($far)->toBe([]);
});

it('measures ita distance from the most recent occurrence of the word', function (): void {
    $defects = detect_defects_for([
        defect_ending(0, 'ب', [], null, 'كتاب'),
        defect_ending(5, 'ب', [], null, 'كتاب'),
        defect_ending(11, 'ب', [], null, 'كتاب'),
    ]);

    expect(array_column($defects, 'bayt'))->toBe([5, 11]);
});

it('ignores empty or missing words for ita', function (): void {
    expect(detect_defects_for([
        defect_ending(0, 'ب', [], null, null),
        defect_ending(1, 'ب', [], null, null),
        defect_ending(2, 'ب', [], null, ''),
        defect_ending(3, 'ب', [], null, ''),
    ]))->toBe([]);
});

it('accepts the reference either wrapped in a result key or as a bare result', function (): void {
    $detector = new RhymeDefectDetector;
    $reference = defect_ending(0, 'ب');
    $endings = [$reference, defect_ending(1, 'م')];

    expect($detector->detect($reference, $endings))->toBe($detector->detect($reference['result'], $endings));
});

it('tolerates endings whose result has no components', function (): void {
    $detector = new RhymeDefectDetector;

    expect($detector->detect(defect_ending(0, 'ب'), [['bayt' => 1, 'result' => []], ['bayt' => 2]]))->toBe([]);
});

it('extracts only sanad defects, preserving order', function (): void {
    $sanad = (new SanadDetector)->fromDefects([
        ['type' => 'rawi_mismatch'],
        ['type' => 'sanad_radf', 'bayt' => 1],
        ['type' => 'iqwa'],
        ['type' => 'sanad_taasis', 'bayt' => 2],
        ['type' => 'ita'],
    ]);

    expect($sanad)->toBe([
        ['type' => 'sanad_radf', 'bayt' => 1],
        ['type' => 'sanad_taasis', 'bayt' => 2],
    ])
        ->and((new SanadDetector)->fromDefects([]))->toBe([])
        ->and((new SanadDetector)->fromDefects([['type' => 'iqwa'], ['bayt' => 3]]))->toBe([]);
});
