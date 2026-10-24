<?php

declare(strict_types=1);

namespace Mosab\QafyaDetector\Support;

/**
 * Machine-readable inventory for important rule IDs currently emitted by Mosab.
 * Descriptions and citations are research metadata, not executable rules.
 */
final class QafyaRuleRegistry
{
    /** @return array<string, array<string, mixed>> */
    public static function all(): array
    {
        return [
            'final_hamza_waw_alef' => self::rule('final_hamza_waw_alef', 'Final hamza followed by wāw and alif-like letter', 'Resolve a common final hamza/wāw/alif pattern; validate with the complete ending and poem context.', ['component', 'tests/Unit/Regression/HamzaRawiCanonicalizationTest.php']),
            'haa_preceded_by_madd_with_final_madd' => self::rule('haa_preceded_by_madd_with_final_madd', 'Hāʾ preceded by a madd letter and followed by final madd', 'Keep hāʾ as rawi and the final madd letter as wasl in this rule family.', ['component', 'tests/Unit/Regression/AyaYaaHaaAndFinalYaaPolicyTest.php']),
            'haa_with_final_madd' => self::rule('haa_with_final_madd', 'Hāʾ followed by a final madd letter', 'Distinguish hāʾ-as-wasl from hāʾ-as-rawi using the preceding letters.', ['component', 'tests/Unit/Regression/AyaYaaHaaAndFinalYaaPolicyTest.php']),
            'vocalized_taa_marbuta_as_taa_rawi' => self::rule('vocalized_taa_marbuta_as_taa_rawi', 'Explicitly vocalized final tāʾ marbūṭa', 'Treat a visibly moving tāʾ marbūṭa as tāʾ rawi rather than automatically treating it as paused hāʾ.', ['component', 'tests/Unit/Regression/FinalAlefTaaMarbutaPauseAsHaaRawiTest.php']),
            'final_haa_or_paused_taa_marbuta_after_alef_like' => self::rule('final_haa_or_paused_taa_marbuta_after_alef_like', 'Final hāʾ or paused tāʾ marbūṭa after alif-like letter', 'Resolve hāʾ/rawi with alif-like radf; preserve the original surface spelling separately.', ['component', 'tests/Unit/Regression/FinalAlefTaaMarbutaPauseAsHaaRawiTest.php']),
            'final_haa_after_waw' => self::rule('final_haa_after_waw', 'Final hāʾ after wāw', 'Keep this family separate from hāʾ after yāʾ and from ordinary pronoun suffixes.', ['component', 'tests/Unit/Regression/WeakAndTaaMarbutaRawiPolicyTest.php']),
            'sakin_haa_after_yaa_pause' => self::rule('sakin_haa_after_yaa_pause', 'Sākin hāʾ after yāʾ in pause spelling', 'Use yāʾ as the rawi family and final hāʾ as wasl where the explicit pause condition holds.', ['component', 'tests/Unit/Regression/AyaYaaHaaAndFinalYaaPolicyTest.php']),
            'vocalized_pronominal_haa_after_yaa' => self::rule('vocalized_pronominal_haa_after_yaa', 'Vocalized pronominal hāʾ after yāʾ', 'Use the hāʾ mark and preceding structure to distinguish a pronoun suffix from a pause spelling.', ['component', 'tests/Unit/Regression/AyaYaaHaaAndFinalYaaPolicyTest.php']),
            'bare_haa_pause_or_ta_marbuta' => self::rule('bare_haa_pause_or_ta_marbuta', 'Bare hāʾ or paused tāʾ marbūṭa', 'A context-sensitive fallback: the previous letter may be rawi and final hāʾ wasl; ambiguity must remain visible.', ['component', 'tests/Unit/Regression/TaaMarbutaSurfaceDisplayTest.php']),
            'bare_aya_ayh_suffix' => self::rule('bare_aya_ayh_suffix', 'Unvocalized ـاية / ـايه family', 'Keep the yāʾ rawi, alif radf, and hāʾ wasl in this family when the structural conditions match.', ['component', 'tests/Unit/Regression/AyaYaaHaaAndFinalYaaPolicyTest.php']),
            'bare_pronominal_haa_after_yaa' => self::rule('bare_pronominal_haa_after_yaa', 'Unvocalized pronoun-like hāʾ after yāʾ', 'Use a conservative rule and retain an ambiguity marker when the ending cannot settle the role.', ['component', 'tests/Unit/Regression/AyaYaaHaaAndFinalYaaPolicyTest.php']),
            'bare_haa' => self::rule('bare_haa', 'Generic final bare hāʾ', 'Conservative fallback requiring contextual review when no stronger family rule matches.', ['component', 'tests/Unit/Detection/WordDetectionTest.php']),
            'poem_level_final_yaa_override' => self::rule('poem_level_final_yaa_override', 'Poem-level final yāʾ arbitration', 'Promote final yāʾ only when the poem pattern supports it; this is a contextual override, not a spelling-only rule.', ['poem', 'tests/Unit/Analysis/FinalYaaPoemOverrideTest.php']),
            'poem_level_taa_marbuta_resolved_as_taa' => self::rule('poem_level_taa_marbuta_resolved_as_taa', 'Poem-level tāʾ marbūṭa/tāʾ family', 'Use the poem-level rhyme context; do not replace the word-level policy without contextual support.', ['poem', 'tests/Unit/Regression/WeakAndTaaMarbutaRawiPolicyTest.php']),
            'final_alef_madda' => self::rule('final_alef_madda', 'Final alif-madda case', 'Preserve the distinction between visible alif-madda and normalized phonological identity.', ['component', 'tests/Unit/Regression/AlefMaddaRegressionTest.php']),
            'final_alef' => self::rule('final_alef', 'Final alif wasl case', 'Resolve final alif-like material with its preceding letter and the pause context.', ['component', 'tests/Unit/Detection/WordDetectionTest.php']),
            'final_weak_after_alef_as_rawi' => self::rule('final_weak_after_alef_as_rawi', 'Final weak letter after alif-like letter', 'Use the full ending rather than a final-glyph-only shortcut.', ['component', 'tests/Unit/Regression/AyaYaaHaaAndFinalYaaPolicyTest.php']),
            'final_waw_yaa' => self::rule('final_waw_yaa', 'Final wāw/yāʾ family', 'Distinguish the final weak letter from the preceding rawi using context.', ['component', 'tests/Unit/Detection/WordDetectionTest.php']),
            'final_alef_maqsura' => self::rule('final_alef_maqsura', 'Final alif maqṣūra', 'Preserve the written form while recording the selected qafya role.', ['component', 'tests/Unit/Detection/FinalAlefMaqsuraWordDetectionTest.php']),
            'final_maqsura_after_hamza_seat' => self::rule('final_maqsura_after_hamza_seat', 'Alif maqṣūra after hamza seat', 'Keep hamza-seat identity separate from visible spelling normalization.', ['component', 'tests/Unit/Regression/HamzaRawiCanonicalizationTest.php']),
            'extraneous_noon_resolved_by_context' => self::rule('extraneous_noon_resolved_by_context', 'Context-resolved extra nūn', 'Do not promote a final nūn without checking the suffix and poem context.', ['component', 'tests/Unit/Regression/FinalNoonRawiRegressionTest.php']),
            'final_consonant' => self::rule('final_consonant', 'Final consonant as rawi fallback', 'A conservative fallback for endings not classified by more specific rules.', ['component', 'tests/Unit/Detection/WordDetectionTest.php']),
            'extraneous_noon' => self::rule('extraneous_noon', 'Unresolved final nūn fallback', 'Do not promote a final nūn without checking suffix and poem context.', ['component', 'tests/Unit/Regression/FinalNoonRawiRegressionTest.php']),
            'poem_level_stable_final_alef_like_wasl' => self::rule('poem_level_stable_final_alef_like_wasl', 'Stable final alif-like wasl', 'Promote the stable pre-final letter when poem-level variation supports it.', ['poem', 'tests/Unit/Analysis/FinalAlefLikePoemOverrideTest.php']),
            'poem_level_final_alef_like_rawi' => self::rule('poem_level_final_alef_like_rawi', 'Final alif-like rawi at poem level', 'Use variation across preceding letters as contextual evidence, not as standalone proof.', ['poem', 'tests/Unit/Analysis/FinalAlefLikePoemOverrideTest.php']),
            'poem_level_collective_waw_alef_after_dominant_rawi' => self::rule('poem_level_collective_waw_alef_after_dominant_rawi', 'Collective wāw/alif after dominant rawi', 'Preserve the dominant rawi and resolve the stable final wāw/alif suffix.', ['poem', 'tests/Unit/Analysis/AlternatingWawYaaRadfPatternTest.php']),
            'poem_level_stable_haa_alef_pronoun_suffix' => self::rule('poem_level_stable_haa_alef_pronoun_suffix', 'Stable hāʾ/alif pronoun suffix', 'Apply only when enough endings and a stable pre-hāʾ letter support the suffix analysis.', ['poem', 'tests/Unit/Regression/StableHaaSuffixPoemOverrideTest.php']),
            'poem_level_stable_yaa_haa_suffix' => self::rule('poem_level_stable_yaa_haa_suffix', 'Stable yāʾ/hāʾ suffix', 'Resolve the yāʾ rawi/hāʾ wasl family from poem-wide evidence.', ['poem', 'tests/Unit/Regression/StableHaaSuffixPoemOverrideTest.php']),
            'single_ending_visible_final_maqsura_pattern' => self::rule('single_ending_visible_final_maqsura_pattern', 'Visible final alif-maqṣūra pattern for a single ending', 'Display-only expansion; do not change the rawi identity.', ['poem', 'tests/Unit/Analysis/FinalAlefLikePoemOverrideTest.php']),
            'poem_level_final_yaa_absorbed_as_kasra_ishbaa' => self::rule('poem_level_final_yaa_absorbed_as_kasra_ishbaa', 'Final yāʾ absorbed as kasra ishbāʿ', 'Do not split a stable tāʾ rawi family because of a final yāʾ extension.', ['poem', 'tests/Unit/Regression/WeakAndTaaMarbutaRawiPolicyTest.php']),
            'poem_level_public_cluster_normalization' => self::rule('poem_level_public_cluster_normalization', 'Public dominant-cluster normalization', 'Remove unstable components from the public dominant payload while preserving per-ending detail.', ['poem', 'tests/Unit/Analysis/PoemLevelAnalysisTest.php']),
        ];
    }

    /** @return array<string, mixed>|null */
    public static function get(string $id): ?array
    {
        return self::all()[$id] ?? null;
    }

    /**
     * @param  list<array<string, mixed>>  $trace
     * @return list<string>
     */
    public static function unknownTraceRules(array $trace): array
    {
        $known = self::all();
        $unknown = [];
        foreach ($trace as $entry) {
            $id = is_array($entry) && is_string($entry['rule'] ?? null) ? $entry['rule'] : null;
            if ($id !== null && ! isset($known[$id])) {
                $unknown[] = $id;
            }
        }

        return array_values(array_unique($unknown));
    }

    /**
     * @param  list<string>  $layers
     * @return array<string, mixed>
     */
    private static function rule(string $id, string $title, string $intent, array $layers): array
    {
        return [
            'id' => $id,
            'title' => $title,
            'intent' => $intent,
            'layers' => $layers,
            'references' => [
                [
                    'work' => 'أحمد الهاشمي، ميزان الذهب في صناعة شعر العرب',
                    'section' => 'باب القافية',
                    'verification_status' => 'bibliographic_pointer_needs_page_level_review',
                ],
            ],
            'scholarly_review_status' => 'pending',
            'test_coverage' => $layers,
            'notes' => 'The registry records implementation intent; it is not a scholarly certification of the rule.',
        ];
    }
}
