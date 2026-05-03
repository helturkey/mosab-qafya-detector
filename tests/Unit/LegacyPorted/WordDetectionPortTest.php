<?php

declare(strict_types=1);

/**
 * Ported word-level coverage from the old helper suite.
 *
 * These assertions target Mosab's object API and scholarly response shape.
 */
dataset('ported_classical_components', [
    'mu_assasa_jahil' => ['جاهل', 'ل', 'ا', 'ه', null],
    'mu_assasa_kamil' => ['كامل', 'ل', 'ا', 'م', null],
    'mu_assasa_aalim' => ['عالم', 'م', 'ا', 'ل', null],
    'mu_assasa_naadir' => ['نادر', 'ر', 'ا', 'د', null],
    'mardoofa_sakoon' => ['سكون', 'ن', null, null, 'و'],
    'mardoofa_qayd' => ['قيد', 'د', null, null, 'ي'],
    'mardoofa_baab' => ['باب', 'ب', null, null, 'ا'],
    'mardoofa_noor' => ['نور', 'ر', null, null, 'و'],
    'mujarrada_qalam' => ['قلم', 'م', null, null, null],
    'mujarrada_sharq' => ['شرق', 'ق', null, null, null],
    'mujarrada_ward' => ['ورد', 'د', null, null, null],
    'mardoofa_kitab' => ['كتاب', 'ب', null, null, 'ا'],
]);

dataset('ported_word_expectations', [
    'qalam' => ['قلم', 'م', null, null, null, null, 'muqayyada'],
    'sharq' => ['شرق', 'ق', null, null, null, null, 'muqayyada'],
    'kitab' => ['كتاب', 'ب', 'ا', null, null, null, 'muqayyada'],
    'sukun' => ['سكون', 'ن', 'و', null, null, null, 'muqayyada'],
    'qayd' => ['قيد', 'د', 'ي', null, null, null, 'muqayyada'],
    'gharbaan' => ['غربا', 'ب', null, 'ا', null, null, 'mutlaqa'],
    'qadi' => ['قاضي', 'ض', 'ا', 'ي', null, null, 'mutlaqa'],
    'jamaluha' => ['جمالها', 'ل', 'ا', 'ه', 'ا', null, 'mutlaqa'],
    'diyaraha' => ['ديارها', 'ر', 'ا', 'ه', 'ا', null, 'mutlaqa'],
    'kitabuhu' => ['كتابُهُ', 'ب', 'ا', 'ه', null, null, 'mutlaqa'],
    'yansahu' => ['ينساهُ', 'ه', 'ا', null, null, null, 'muqayyada'],
    'yaduuhu' => ['يدعوهُ', 'ه', 'و', null, null, null, 'muqayyada'],
    'alayhi' => ['عَلَيهِ', 'ل', 'ي', 'ه', null, null, 'mutlaqa'],
    'artadihi' => ['أَرتَضيهِ', 'ض', 'ي', 'ه', null, null, 'mutlaqa'],
    'sundusiyyah' => ['سُندُسِيَّه', 'ي', null, 'ه', null, null, 'mutlaqa'],
    'naahiyah' => ['النّاحيَهْ', 'ي', null, 'ه', null, null, 'mutlaqa'],
    'hamza_waw_alef' => ['وجأوا', 'ء', null, 'و', 'ا', null, 'mutlaqa'],
]);

dataset('ported_subtype_expectations', [
    'mut_muassasa_haa' => ['جاهلها', 'mutlaqa_muassasa_connected_with_haa'],
    'mut_muassasa_madd' => ['جاهلا', 'mutlaqa_muassasa_connected_with_madd'],
    'mut_mardoofa_haa' => ['سكونها', 'mutlaqa_mardoofa_connected_with_haa'],
    'mut_mardoofa_madd' => ['سكونا', 'mutlaqa_mardoofa_connected_with_madd'],
    'mut_mujarrada' => ['كتبا', 'mutlaqa_mujarrada'],
    'muq_muassasa' => ['جاهل', 'muqayyada_muassasa'],
    'muq_mardoofa' => ['سكون', 'muqayyada_mardoofa'],
    'muq_mujarrada' => ['قلم', 'muqayyada_mujarrada'],
]);

it('extracts classical components from representative words', function (
    string $word,
    string $rawi,
    ?string $taasis,
    ?string $dakhiil,
    ?string $radf,
): void {
    $result = qafya_detector()->extract($word);

    expect($result->rawi())->toBe($rawi)
        ->and($result->taasis())->toBe($taasis)
        ->and($result->dakhiil())->toBe($dakhiil)
        ->and($result->radf())->toBe($radf);
})->with('ported_classical_components');

it('detects rawi, radf, wasl, khurooj and category for core edge words', function (
    string $word,
    string $rawi,
    ?string $radf,
    ?string $wasl,
    ?string $khurooj,
    ?string $taasis,
    string $category,
): void {
    $result = qafya_detector()->extract($word);

    expect($result->rawi())->toBe($rawi)
        ->and($result->radf())->toBe($radf)
        ->and($result->wasl())->toBe($wasl)
        ->and($result->khurooj())->toBe($khurooj)
        ->and($result->taasis())->toBe($taasis)
        ->and($result->category())->toBe($category)
        ->and($result->status())->toBe('ok');
})->with('ported_word_expectations');

it('classifies the reachable classical qafya subtypes', function (string $word, string $expectedSubtype): void {
    expect(qafya_detector()->extract($word)->subtype())->toBe($expectedSubtype);
})->with('ported_subtype_expectations');

it('keeps haa role distinctions visible at word level', function (): void {
    $detector = qafya_detector();
    $kitabuhu = $detector->extract('كتابُهُ');
    $yansahu = $detector->extract('ينساهُ');
    $yaduuhu = $detector->extract('يدعوهُ');
    $alayhi = $detector->extract('عَلَيهِ');

    expect($kitabuhu->rawi())->toBe('ب')
        ->and($kitabuhu->radf())->toBe('ا')
        ->and($kitabuhu->wasl())->toBe('ه')
        ->and($kitabuhu->patternSurface())->toBe('ابه')
        ->and($yansahu->rawi())->toBe('ه')
        ->and($yansahu->radf())->toBe('ا')
        ->and($yansahu->wasl())->toBeNull()
        ->and($yansahu->patternSurface())->toBe('اه')
        ->and($yaduuhu->rawi())->toBe('ه')
        ->and($yaduuhu->radf())->toBe('و')
        ->and($yaduuhu->wasl())->toBeNull()
        ->and($yaduuhu->patternSurface())->toBe('وه')
        ->and($alayhi->rawi())->toBe('ل')
        ->and($alayhi->radf())->toBe('ي')
        ->and($alayhi->wasl())->toBe('ه');
});

it('keeps bare final alef as wasl but written alef-maqsura as rawi at word level', function (): void {
    $result = qafya_detector()->extract('الصدى');

    expect($result->rawi())->toBe('ى')
        ->and($result->wasl())->toBeNull()
        ->and($result->patternSurface())->toBe('ى')
        ->and($result->category())->toBe('muqayyada');
});

it('exposes haraka-aware diagnostics and motions for diacritized words', function (): void {
    $kitab = qafya_detector()->extract('كِتَابٌ');
    $manzil = qafya_detector()->extract('مَنزِلِ');

    expect($kitab->rawi())->toBe('ب')
        ->and($kitab->rawiHaraka())->toBe('ٌ')
        ->and($kitab->rawiHarakaName())->toBe('تنوين ضم')
        ->and($kitab->mujra())->toBe('damma')
        ->and($manzil->rawiHaraka())->toBe('ِ')
        ->and($manzil->mujra())->toBe('kasra');
});

it('builds explicit signatures that separate component, strict, cluster and visual forms', function (): void {
    $jamal = qafya_detector()->extract('جمالها');
    $diyar = qafya_detector()->extract('ديارها');

    expect($jamal->componentSignature())->toBe('Fا-Rل-Wه-Kا')
        ->and($jamal->strictSignature())->toBe('Fا-Rل-Wه-Kا|mujra=?')
        ->and($jamal->clusterSignature())->toBe('Rل-Wه-Kا')
        ->and($jamal->patternSurface())->toBe('الها')
        ->and($jamal->strictSignature())->not->toBe($diyar->strictSignature());
});

it('returns partial results for empty or non-Arabic endings without throwing', function (): void {
    $empty = qafya_detector()->extract('');
    $latin = qafya_detector()->extract('--- abc ---');

    expect($empty->status())->toBe('partial')
        ->and($empty->rawi())->toBeNull()
        ->and($empty->confidenceScore())->toBe(0.0)
        ->and($latin->status())->toBe('partial');
});
