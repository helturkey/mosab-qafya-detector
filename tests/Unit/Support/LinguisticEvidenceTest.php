<?php

declare(strict_types=1);

use Mosab\QafyaDetector\Data\LinguisticEvidence;
use Mosab\QafyaDetector\Support\QafyaOptions;

it('keeps optional language-tool output explicitly non-authoritative', function (): void {
    $evidence = new LinguisticEvidence(
        provider: 'test-adapter',
        suggestions: [['text' => 'كَلِمَة', 'score' => 0.7]],
        morphology: [['lemma' => 'كلمة']],
        confidence: 0.7,
    );

    expect($evidence->toArray()['used_to_override_qafya'])->toBeFalse()
        ->and($evidence->toArray()['confidence_semantics'])->toBe('provider_reported_score_not_verified_probability');
});

it('leaves optional language analysis disabled by default', function (): void {
    expect(QafyaOptions::normalize()->useLinguisticEvidence)->toBeFalse()
        ->and(QafyaOptions::normalize(['use_linguistic_evidence' => true])->useLinguisticEvidence)->toBeTrue();
});
