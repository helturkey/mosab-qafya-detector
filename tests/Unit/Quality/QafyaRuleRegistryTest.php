<?php

declare(strict_types=1);

use Mosab\QafyaDetector\Support\QafyaRuleRegistry;

it('documents special-ending rules with intent and test pointers', function (): void {
    $rule = QafyaRuleRegistry::get('bare_aya_ayh_suffix');

    expect($rule)->toBeArray()
        ->and($rule['intent'])->toBeString()
        ->and($rule['test_coverage'])->toContain('tests/Unit/Regression/AyaYaaHaaAndFinalYaaPolicyTest.php')
        ->and($rule['scholarly_review_status'])->toBe('pending');
});

it('detects trace rules that have not yet been registered', function (): void {
    expect(QafyaRuleRegistry::unknownTraceRules([
        ['rule' => 'bare_haa'],
        ['rule' => 'brand_new_unregistered_rule'],
    ]))->toBe(['brand_new_unregistered_rule']);
});
