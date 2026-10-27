<?php

declare(strict_types=1);

use Mosab\QafyaDetector\Classification\QafyaSignatureBuilder;
use Mosab\QafyaDetector\Classification\QafyaSubtypeClassifier;

it('builds component, strict, cluster, and visual signatures in qafya order', function (): void {
    $signature = (new QafyaSignatureBuilder)->build(
        ['radf' => ['letter' => 'ا'], 'rawi' => ['letter' => 'ب'], 'wasl' => ['letter' => 'ه']],
        ['mujra' => 'damma'],
    );

    expect($signature)->toBe([
        'component' => 'Fا-Rب-Wه',
        'strict' => 'Fا-Rب-Wه|mujra=damma',
        'cluster' => 'Rب-Wه',
        'visual' => 'ابه',
    ]);
});

it('includes taasis and dakhiil in the component signature but not in the cluster', function (): void {
    $signature = (new QafyaSignatureBuilder)->build(
        ['taasis' => ['letter' => 'ا'], 'dakhiil' => ['letter' => 'ص'], 'rawi' => ['letter' => 'د']],
        ['mujra' => 'sukun'],
    );

    expect($signature['component'])->toBe('Tا-Dص-Rد')
        ->and($signature['cluster'])->toBe('Rد')
        ->and($signature['visual'])->toBe('اصد');
});

it('includes khurooj in the cluster signature', function (): void {
    $signature = (new QafyaSignatureBuilder)->build(
        ['rawi' => ['letter' => 'ء'], 'wasl' => ['letter' => 'و'], 'khurooj' => ['letter' => 'ا']],
        [],
    );

    expect($signature['component'])->toBe('Rء-Wو-Kا')
        ->and($signature['cluster'])->toBe('Rء-Wو-Kا')
        ->and($signature['strict'])->toBe('Rء-Wو-Kا|mujra=?');
});

it('marks an unknown mujra with a question mark', function (): void {
    $signature = (new QafyaSignatureBuilder)->build(['rawi' => ['letter' => 'ب']], ['mujra' => null]);

    expect($signature['strict'])->toBe('Rب|mujra=?');
});

it('returns null signatures and an empty visual when there are no components', function (): void {
    $signature = (new QafyaSignatureBuilder)->build([], []);

    expect($signature)->toBe(['component' => null, 'strict' => null, 'cluster' => null, 'visual' => '']);
});

it('ignores malformed or empty component entries', function (): void {
    $signature = (new QafyaSignatureBuilder)->build(
        ['radf' => null, 'taasis' => ['letter' => ''], 'dakhiil' => 'ص', 'rawi' => ['letter' => 'ب']],
        [],
    );

    expect($signature['component'])->toBe('Rب')
        ->and($signature['visual'])->toBe('ب');
});

it('classifies mutlaqa subtypes from taasis, radf, and the wasl letter', function (): void {
    $classifier = new QafyaSubtypeClassifier;
    $letter = static fn (string $value): array => ['letter' => $value];

    expect($classifier->classify('mutlaqa', ['taasis' => $letter('ا'), 'wasl' => $letter('ه')]))->toBe('mutlaqa_muassasa_connected_with_haa')
        ->and($classifier->classify('mutlaqa', ['taasis' => $letter('ا'), 'wasl' => $letter('ا')]))->toBe('mutlaqa_muassasa_connected_with_madd')
        ->and($classifier->classify('mutlaqa', ['radf' => $letter('ا'), 'wasl' => $letter('ه')]))->toBe('mutlaqa_mardoofa_connected_with_haa')
        ->and($classifier->classify('mutlaqa', ['radf' => $letter('ا'), 'wasl' => $letter('ي')]))->toBe('mutlaqa_mardoofa_connected_with_madd')
        ->and($classifier->classify('mutlaqa', ['radf' => $letter('ا')]))->toBe('mutlaqa_mardoofa_connected_with_liin')
        ->and($classifier->classify('mutlaqa', ['wasl' => $letter('ا')]))->toBe('mutlaqa_mujarrada')
        ->and($classifier->classify('mutlaqa', []))->toBe('mutlaqa_mujarrada');
});

it('lets taasis outrank radf and treats a taa marbuta wasl as haa', function (): void {
    $classifier = new QafyaSubtypeClassifier;
    $letter = static fn (string $value): array => ['letter' => $value];

    expect($classifier->classify('mutlaqa', ['taasis' => $letter('ا'), 'radf' => $letter('ا'), 'wasl' => $letter('ه')]))->toBe('mutlaqa_muassasa_connected_with_haa')
        ->and($classifier->classify('mutlaqa', ['radf' => $letter('ا'), 'wasl' => $letter('ة')]))->toBe('mutlaqa_mardoofa_connected_with_haa')
        ->and($classifier->classify('mutlaqa', ['radf' => $letter('ا'), 'wasl' => $letter('ى')]))->toBe('mutlaqa_mardoofa_connected_with_madd');
});

it('classifies muqayyada subtypes from taasis and radf', function (): void {
    $classifier = new QafyaSubtypeClassifier;
    $letter = static fn (string $value): array => ['letter' => $value];

    expect($classifier->classify('muqayyada', ['taasis' => $letter('ا')]))->toBe('muqayyada_muassasa')
        ->and($classifier->classify('muqayyada', ['radf' => $letter('و')]))->toBe('muqayyada_mardoofa')
        ->and($classifier->classify('muqayyada', ['taasis' => $letter('ا'), 'radf' => $letter('و')]))->toBe('muqayyada_muassasa')
        ->and($classifier->classify('muqayyada', []))->toBe('muqayyada_mujarrada')
        ->and($classifier->classify('unknown', []))->toBe('muqayyada_mujarrada');
});

it('exposes subtype labels and descriptions and tolerates unknown values', function (): void {
    $classifier = new QafyaSubtypeClassifier;

    expect($classifier->label('mutlaqa_mujarrada'))->toBe('مطلقة مجردة')
        ->and($classifier->description('mutlaqa_mujarrada'))->toBe('قافية مطلقة لا تأسيس فيها ولا ردف.')
        ->and($classifier->label('unknown'))->toBe('غير معروف')
        ->and($classifier->label(null))->toBeNull()
        ->and($classifier->description(null))->toBeNull()
        ->and($classifier->label('not_a_subtype'))->toBeNull()
        ->and($classifier->description('not_a_subtype'))->toBeNull();
});
