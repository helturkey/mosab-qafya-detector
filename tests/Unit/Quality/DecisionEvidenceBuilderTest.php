<?php

declare(strict_types=1);

use Mosab\QafyaDetector\Analysis\DecisionEvidenceBuilder;

it('connects selected rule results to evidence and explicit uncertainty', function (): void {
    $diagnostics = (new DecisionEvidenceBuilder)->build([
        'confidence' => ['score' => 0.83, 'level' => 'high'],
        'trace' => [
            ['rule' => 'bare_aya_ayh_suffix', 'decision' => 'yaa_rawi_alef_radf_final_haa_wasl'],
        ],
        'warnings' => [],
        'ambiguities' => [['type' => 'example_ambiguity']],
    ], [
        'rawi' => ['letter' => 'ي'],
        'radf' => ['letter' => 'ا'],
        'wasl' => ['letter' => 'ه'],
    ]);

    expect($diagnostics['evidence'][0]['rule_id'])->toBe('bare_aya_ayh_suffix')
        ->and($diagnostics['candidates'][0]['selected'])->toBeTrue()
        ->and($diagnostics['candidates'][0]['alternatives_exhaustive'])->toBeFalse()
        ->and($diagnostics['uncertainty']['candidate_set_complete'])->toBeFalse()
        ->and($diagnostics['uncertainty']['requires_review'])->toBeTrue();
});

it('exposes an unranked final-haa rawi hypothesis when the detector marks a role ambiguity', function (): void {
    $diagnostics = (new DecisionEvidenceBuilder)->build([
        'confidence' => ['score' => 0.7],
        'trace' => [['rule' => 'bare_haa', 'decision' => 'previous_letter_rawi_haa_wasl']],
        'warnings' => [],
        'ambiguities' => [['type' => 'haa_role_needs_poem_context', 'letter' => 'ه']],
    ], [
        'rawi' => ['letter' => 'ب'],
        'wasl' => ['letter' => 'ه'],
    ]);

    expect($diagnostics['candidates'])->toHaveCount(2)
        ->and($diagnostics['candidates'][1]['selected'])->toBeFalse()
        ->and($diagnostics['candidates'][1]['rawi'])->toBe('ه')
        ->and($diagnostics['candidates'][1]['evidence_score'])->toBeNull()
        ->and($diagnostics['candidates'][1]['alternatives_exhaustive'])->toBeFalse();
});
