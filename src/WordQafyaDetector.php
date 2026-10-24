<?php

declare(strict_types=1);

namespace Mosab\QafyaDetector;

use Mosab\QafyaDetector\Analysis\DecisionEvidenceBuilder;
use Mosab\QafyaDetector\Classification\QafyaNameClassifier;
use Mosab\QafyaDetector\Classification\QafyaSignatureBuilder;
use Mosab\QafyaDetector\Classification\QafyaSubtypeClassifier;
use Mosab\QafyaDetector\Contracts\LinguisticEvidenceProvider;
use Mosab\QafyaDetector\Contracts\WordQafyaDetectorContract;
use Mosab\QafyaDetector\Data\QafyaResult;
use Mosab\QafyaDetector\Detection\ComponentDetector;
use Mosab\QafyaDetector\Enums\QafyaCategory;
use Mosab\QafyaDetector\Enums\QafyaStatus;
use Mosab\QafyaDetector\Enums\QafyaSubtype;
use Mosab\QafyaDetector\Support\ConfidenceCalibrationProfile;
use Mosab\QafyaDetector\Support\QafyaOptions;
use Mosab\QafyaDetector\Support\QafyaSurfaceFormatter;
use Mosab\QafyaDetector\Text\QafyaSegmentExtractor;
use Throwable;

/** Production word/ending qafya detector with optional, non-authoritative evidence. */
final class WordQafyaDetector implements WordQafyaDetectorContract
{
    public function __construct(
        private readonly ComponentDetector $components = new ComponentDetector,
        private readonly QafyaSegmentExtractor $segments = new QafyaSegmentExtractor,
        private readonly QafyaNameClassifier $names = new QafyaNameClassifier,
        private readonly QafyaSubtypeClassifier $subtypes = new QafyaSubtypeClassifier,
        private readonly QafyaSignatureBuilder $signatures = new QafyaSignatureBuilder,
        private readonly DecisionEvidenceBuilder $evidenceBuilder = new DecisionEvidenceBuilder,
        private readonly ?LinguisticEvidenceProvider $linguisticEvidenceProvider = null,
    ) {}

    /** @param array<string, mixed>|QafyaOptions $options */
    public function detect(string $ending, array|QafyaOptions $options = []): QafyaResult
    {
        return QafyaResult::fromArray($this->detectArray($ending, $options));
    }

    /**
     * @param  array<string, mixed>|QafyaOptions  $options
     * @return array<string, mixed>
     */
    public function detectArray(string $ending, array|QafyaOptions $options = []): array
    {
        $options = QafyaOptions::normalize($options);
        $mode = $options->resolvedModeFor($ending);
        $componentData = $this->components->detect($ending);
        $segment = $this->segments->extract($ending, $mode, $options->strictHarakat);
        $components = is_array($componentData['components'] ?? null) ? $componentData['components'] : [];
        $motions = is_array($componentData['motions'] ?? null) ? $componentData['motions'] : [];
        $category = is_string($componentData['category'] ?? null) ? $componentData['category'] : 'unknown';
        $subtype = $this->subtypes->classify($category, $components);
        $name = $this->names->classify($segment['moving_count_between_sakins']);
        $categoryEnum = QafyaCategory::tryFrom($category);
        $subtypeEnum = QafyaSubtype::tryFrom($subtype);
        $signatures = $this->signatures->build($components, $motions);
        $patternSurface = is_string($componentData['surface_pattern'] ?? null)
            ? $componentData['surface_pattern']
            : $signatures['visual'];
        $rawiComponent = is_array($components['rawi'] ?? null) ? $components['rawi'] : [];
        $radfComponent = is_array($components['radf'] ?? null) ? $components['radf'] : [];
        $waslComponent = is_array($components['wasl'] ?? null) ? $components['wasl'] : [];
        $rawiLetter = is_string($rawiComponent['letter'] ?? null) ? $rawiComponent['letter'] : null;
        $radfLetter = is_string($radfComponent['letter'] ?? null) ? $radfComponent['letter'] : null;
        $waslLetter = is_string($waslComponent['letter'] ?? null) ? $waslComponent['letter'] : null;
        $surfaceWord = is_string($componentData['word'] ?? null) ? $componentData['word'] : $ending;
        $patternSurface = QafyaSurfaceFormatter::preserveTaaMarbuta(
            $patternSurface,
            $surfaceWord,
            $rawiLetter,
            $radfLetter,
            $waslLetter,
        );
        $signatures['visual'] = $patternSurface;
        $pattern = [
            'surface' => $patternSurface,
            'component' => $signatures['component'],
            'strict' => $signatures['strict'],
            'cluster' => $signatures['cluster'],
            'canonical' => $signatures['component'],
        ];

        $segmentWarnings = array_map(static fn (string $warning): array => ['type' => $warning], $segment['warnings']);
        $componentWarnings = is_array($componentData['warnings'] ?? null) ? $componentData['warnings'] : [];
        $warnings = array_merge($segmentWarnings, $componentWarnings);

        $linguisticEvidence = null;
        if ($options->useLinguisticEvidence) {
            if ($this->linguisticEvidenceProvider === null) {
                $warnings[] = ['type' => 'linguistic_evidence_provider_unavailable'];
            } else {
                try {
                    $provided = $this->linguisticEvidenceProvider->analyze($ending, [
                        'mode' => $mode,
                        'source' => 'qafya_ending',
                        'role' => 'supplemental_evidence_only',
                    ]);
                    $linguisticEvidence = $provided->toArray();
                } catch (Throwable $exception) {
                    // Do not leak provider exception messages into API output.
                    $warnings[] = [
                        'type' => 'linguistic_evidence_provider_failed',
                        'exception' => $exception::class,
                    ];
                }
            }
        }

        $decisionEvidence = $this->evidenceBuilder->build($componentData, $components);
        $confidence = is_array($componentData['confidence'] ?? null)
            ? $componentData['confidence']
            : ['score' => 0.0, 'level' => 'none'];
        $confidence['basis'] = is_string($confidence['basis'] ?? null)
            ? $confidence['basis']
            : 'rule_based_evidence_score';
        $confidence['calibrated'] = (bool) ($confidence['calibrated'] ?? false);
        $confidence['calibrated_score'] = null;
        $confidence['calibration_profile_id'] = null;
        $confidence['has_calibrated_estimate'] = false;

        if ($options->confidenceCalibrationProfile !== null) {
            $profile = new ConfidenceCalibrationProfile($options->confidenceCalibrationProfile);
            $calibratedScore = $profile->calibrate((float) ($confidence['score'] ?? 0.0));
            if ($calibratedScore !== null) {
                $confidence['calibrated_score'] = $calibratedScore;
                $confidence['calibration_profile_id'] = $profile->id();
                $confidence['has_calibrated_estimate'] = true;
            }
        }
        $decisionEvidence['uncertainty']['calibrated_estimate_available'] = $confidence['has_calibrated_estimate'];
        $decisionEvidence['uncertainty']['calibration_profile_id'] = $confidence['calibration_profile_id'];

        return [
            'status' => ($components['rawi'] ?? null) !== null ? QafyaStatus::Ok->value : QafyaStatus::Partial->value,
            'input' => [
                'text' => $ending,
                'word' => is_string($componentData['word'] ?? null) ? $componentData['word'] : $ending,
                'normalized' => is_string($componentData['normalized_word'] ?? null) ? $componentData['normalized_word'] : '',
                'source' => 'ending',
                'mode' => [
                    'requested' => $options->mode,
                    'resolved' => $mode,
                    'strict_harakat' => $options->strictHarakat,
                ],
            ],
            'segment' => $segment,
            'qafya' => [
                'category' => $category,
                'category_label' => $categoryEnum?->label(),
                'category_description' => $categoryEnum?->description(),
                'subtype' => $subtype,
                'subtype_label' => $subtypeEnum?->label(),
                'subtype_description' => $subtypeEnum?->description(),
                'name' => $name,
                'name_arabic' => $this->names->arabicName($name),
                'name_description' => $this->names->description($name),
                'segment' => $segment,
                'boundary' => $segment,
                'pattern' => $pattern,
                'components' => $components,
                'motions' => $motions,
            ],
            'signatures' => $signatures,
            'pattern' => $pattern,
            'confidence' => $confidence,
            'diagnostics' => [
                'warnings' => $warnings,
                'ambiguities' => is_array($componentData['ambiguities'] ?? null) ? $componentData['ambiguities'] : [],
                'candidates' => $componentData['candidates'] ?? $decisionEvidence['candidates'],
                'evidence' => $decisionEvidence['evidence'],
                'uncertainty' => $decisionEvidence['uncertainty'],
                'linguistic_evidence' => $linguisticEvidence,
                'defects' => [],
            ],
            'trace' => is_array($componentData['trace'] ?? null) ? $componentData['trace'] : [],
        ];
    }
}
