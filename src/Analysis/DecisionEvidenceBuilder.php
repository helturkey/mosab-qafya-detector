<?php

declare(strict_types=1);

namespace Mosab\QafyaDetector\Analysis;

use Mosab\QafyaDetector\Support\QafyaRuleRegistry;

/** Normalizes the existing rule trace into explainable diagnostics. */
final class DecisionEvidenceBuilder
{
    /**
     * @param  array<string, mixed>  $componentData
     * @param  array<string, mixed>  $components
     * @return array{evidence:list<array<string,mixed>>,uncertainty:array<string,mixed>,candidates:list<array<string,mixed>>}
     */
    public function build(array $componentData, array $components): array
    {
        /** @var list<array<string, mixed>> $trace */
        $trace = [];
        $rawTrace = $componentData['trace'] ?? null;
        if (is_array($rawTrace)) {
            foreach ($rawTrace as $traceEntry) {
                if (is_array($traceEntry)) {
                    /** @var array<string, mixed> $traceEntry */
                    $trace[] = $traceEntry;
                }
            }
        }
        $warnings = is_array($componentData['warnings'] ?? null) ? $componentData['warnings'] : [];
        $ambiguities = is_array($componentData['ambiguities'] ?? null) ? $componentData['ambiguities'] : [];
        $score = is_array($componentData['confidence'] ?? null)
            ? (float) ($componentData['confidence']['score'] ?? 0.0)
            : 0.0;

        $evidence = [];
        $supportingRules = [];
        foreach ($trace as $entry) {
            $rule = is_string($entry['rule'] ?? null) ? $entry['rule'] : null;
            $decision = is_string($entry['decision'] ?? null) ? $entry['decision'] : null;
            if ($rule !== null) {
                $supportingRules[] = $rule;
            }
            $evidence[] = [
                'kind' => 'rule_trace',
                'rule_id' => $rule,
                'decision' => $decision,
                'details' => $entry,
                'score_contribution' => 'not_individually_calibrated',
            ];
        }

        foreach ($warnings as $warning) {
            if (is_string($warning)) {
                $evidence[] = ['kind' => 'warning', 'code' => $warning];
            } elseif (is_array($warning)) {
                $evidence[] = ['kind' => 'warning'] + $warning;
            }
        }
        foreach ($ambiguities as $ambiguity) {
            if (is_array($ambiguity)) {
                $evidence[] = ['kind' => 'ambiguity'] + $ambiguity;
            }
        }

        $unregisteredRules = QafyaRuleRegistry::unknownTraceRules($trace);
        foreach ($unregisteredRules as $ruleId) {
            $evidence[] = ['kind' => 'unregistered_rule', 'rule_id' => $ruleId];
        }

        $reasons = [];
        if ($unregisteredRules !== []) {
            $reasons[] = 'unregistered_trace_rule';
        }
        if ($ambiguities !== []) {
            $reasons[] = 'detector_reported_ambiguity';
        }
        foreach ($warnings as $warning) {
            $code = is_string($warning) ? $warning : (is_array($warning) && is_string($warning['type'] ?? null) ? $warning['type'] : null);
            if ($code !== null && preg_match('/undiacritized|partial|estimated|fallback|ambiguous|needs_review/u', $code) === 1) {
                $reasons[] = $code;
            }
        }
        $reasons = array_values(array_unique($reasons));

        $rawi = is_array($components['rawi'] ?? null) ? ($components['rawi']['letter'] ?? null) : null;
        $candidates = [];
        if (is_string($rawi) && $rawi !== '') {
            $candidates[] = [
                'id' => 'selected_rule_based_result',
                'selected' => true,
                'rawi' => $rawi,
                'components' => $this->componentLetters($components),
                'supporting_rules' => array_values(array_unique($supportingRules)),
                'evidence_score' => $score,
                'score_kind' => 'rule_based_evidence_score',
                'calibrated' => false,
                'alternatives_exhaustive' => false,
            ];
        }

        // Preserve a concrete alternative when the component detector itself
        // marks final hāʾ role as context-dependent. It is deliberately
        // unranked: the current detector has not scored this hypothesis.
        $hasHaaRoleAmbiguity = false;
        foreach ($ambiguities as $ambiguity) {
            if (is_array($ambiguity) && ($ambiguity['type'] ?? null) === 'haa_role_needs_poem_context') {
                $hasHaaRoleAmbiguity = true;
                break;
            }
        }
        if ($hasHaaRoleAmbiguity && $rawi !== 'ه') {
            $candidates[] = [
                'id' => 'alternate_final_haa_as_rawi',
                'selected' => false,
                'hypothesis' => 'final_haa_is_rawi',
                'rawi' => 'ه',
                'components' => [
                    'taasis' => null,
                    'dakhiil' => null,
                    'radf' => null,
                    'rawi' => 'ه',
                    'wasl' => null,
                    'khurooj' => null,
                ],
                'supporting_rules' => [],
                'evidence_score' => null,
                'score_kind' => 'unranked_structural_hypothesis',
                'calibrated' => false,
                'alternatives_exhaustive' => false,
                'triggered_by' => 'haa_role_needs_poem_context',
            ];
        }

        return [
            'evidence' => $evidence,
            'uncertainty' => [
                'requires_review' => $reasons !== [],
                'reasons' => $reasons,
                // The current detector has not implemented exhaustive candidate enumeration.
                'candidate_set_complete' => false,
                'confidence_calibrated' => false,
                'unregistered_rules' => $unregisteredRules,
            ],
            'candidates' => $candidates,
        ];
    }

    /**
     * @param  array<string, mixed>  $components
     * @return array<string, ?string>
     */
    private function componentLetters(array $components): array
    {
        $letters = [];
        foreach (['taasis', 'dakhiil', 'radf', 'rawi', 'wasl', 'khurooj'] as $name) {
            $value = $components[$name]['letter'] ?? null;
            $letters[$name] = is_string($value) ? $value : null;
        }

        return $letters;
    }
}
