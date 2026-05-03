<?php

declare(strict_types=1);

namespace Mosab\QafyaDetector;

use Mosab\QafyaDetector\Classification\QafyaNameClassifier;
use Mosab\QafyaDetector\Classification\QafyaSignatureBuilder;
use Mosab\QafyaDetector\Classification\QafyaSubtypeClassifier;
use Mosab\QafyaDetector\Contracts\WordQafyaDetectorContract;
use Mosab\QafyaDetector\Data\QafyaResult;
use Mosab\QafyaDetector\Detection\ComponentDetector;
use Mosab\QafyaDetector\Enums\QafyaCategory;
use Mosab\QafyaDetector\Enums\QafyaStatus;
use Mosab\QafyaDetector\Enums\QafyaSubtype;
use Mosab\QafyaDetector\Support\QafyaOptions;
use Mosab\QafyaDetector\Text\QafyaSegmentExtractor;

/**
 * Production word/ending qafya detector. No legacy helper is used.
 */
final class WordQafyaDetector implements WordQafyaDetectorContract
{
    public function __construct(
        private readonly ComponentDetector $components = new ComponentDetector,
        private readonly QafyaSegmentExtractor $segments = new QafyaSegmentExtractor,
        private readonly QafyaNameClassifier $names = new QafyaNameClassifier,
        private readonly QafyaSubtypeClassifier $subtypes = new QafyaSubtypeClassifier,
        private readonly QafyaSignatureBuilder $signatures = new QafyaSignatureBuilder,
    ) {}

    /**
     * @param  array<string, mixed>|QafyaOptions  $options
     */
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
        $segment = $this->segments->extract($ending);
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
            'confidence' => is_array($componentData['confidence'] ?? null) ? $componentData['confidence'] : ['score' => 0.0, 'level' => 'none'],
            'diagnostics' => [
                'warnings' => $warnings,
                'ambiguities' => is_array($componentData['ambiguities'] ?? null) ? $componentData['ambiguities'] : [],
                'candidates' => is_array($componentData['candidates'] ?? null) ? $componentData['candidates'] : [],
                'defects' => [],
            ],
            'trace' => is_array($componentData['trace'] ?? null) ? $componentData['trace'] : [],
        ];
    }
}
