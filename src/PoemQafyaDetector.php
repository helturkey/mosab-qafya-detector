<?php

declare(strict_types=1);

namespace Mosab\QafyaDetector;

use Mosab\QafyaDetector\Analysis\PoemAnalysisPresenter;
use Mosab\QafyaDetector\Analysis\PoemClusterAnalyzer;
use Mosab\QafyaDetector\Analysis\PoemEndingCollector;
use Mosab\QafyaDetector\Analysis\PoemLevelRulePipeline;
use Mosab\QafyaDetector\Analysis\PublicDominantQafyaNormalizer;
use Mosab\QafyaDetector\Analysis\QafyaObligationResolver;
use Mosab\QafyaDetector\Analysis\RhymeDefectDetector;
use Mosab\QafyaDetector\Analysis\SanadDetector;
use Mosab\QafyaDetector\Contracts\PoemQafyaDetectorContract;
use Mosab\QafyaDetector\Data\PoemQafyaAnalysis;
use Mosab\QafyaDetector\Support\QafyaOptions;

/**
 * Production poem-level qafya analyzer.
 *
 * This class is intentionally thin. The heavy decisions are split into:
 * - PoemEndingCollector: text splitting and word-level detection
 * - PoemLevelRulePipeline: poem-context qafya laws for weak/special endings
 * - PoemClusterAnalyzer: rawi-level public clustering and consistency notes
 * - PublicDominantQafyaNormalizer: safe dominant qafya payload normalization
 * - PoemAnalysisPresenter: response/indexing fragments
 */
final class PoemQafyaDetector implements PoemQafyaDetectorContract
{
    private const AUTHORITATIVE_DOMINANT_RATIO = 0.8;

    public function __construct(
        private readonly PoemEndingCollector $endingCollector = new PoemEndingCollector,
        private readonly PoemLevelRulePipeline $rulePipeline = new PoemLevelRulePipeline,
        private readonly PoemClusterAnalyzer $clusterAnalyzer = new PoemClusterAnalyzer,
        private readonly PublicDominantQafyaNormalizer $dominantNormalizer = new PublicDominantQafyaNormalizer,
        private readonly PoemAnalysisPresenter $presenter = new PoemAnalysisPresenter,
        private readonly QafyaObligationResolver $obligations = new QafyaObligationResolver,
        private readonly RhymeDefectDetector $defects = new RhymeDefectDetector,
        private readonly SanadDetector $sanad = new SanadDetector,
    ) {}

    /**
     * @param  string|array<int, string>  $poem
     * @param  array<string, mixed>|QafyaOptions  $options
     */
    public function analyze(string|array $poem, array|QafyaOptions $options = []): PoemQafyaAnalysis
    {
        return PoemQafyaAnalysis::fromArray($this->analyzeArray($poem, $options));
    }

    /**
     * @param  string|array<int, string>  $poem
     * @param  array<string, mixed>|QafyaOptions  $options
     * @return array<string, mixed>
     */
    public function analyzeArray(string|array $poem, array|QafyaOptions $options = []): array
    {
        $options = QafyaOptions::normalize($options);
        $collected = $this->endingCollector->collect($poem, $options);

        $lines = $collected['lines'];
        $pairs = $collected['pairs'];
        $endings = $collected['endings'];

        if ($pairs === []) {
            return $this->empty('empty_or_unpaired_poem', count($lines));
        }

        $endings = $this->rulePipeline->apply($endings);

        $reference = $this->clusterAnalyzer->firstValid($endings);
        if ($reference === null) {
            return $this->empty('no_valid_qafya_reference', count($lines));
        }

        $referenceResult = is_array($reference['result'] ?? null) ? $reference['result'] : [];
        $obligations = $this->obligations->resolve($referenceResult);
        $clusters = $this->clusterAnalyzer->clusters($endings);
        $dominant = $clusters[0] ?? ['signature' => null, 'count' => 0, 'ratio' => 0.0];
        $defects = $options->detectDefects ? $this->defects->detect($reference, $endings) : [];
        $sanad = $options->detectSanad ? $this->sanad->fromDefects($defects) : [];

        $dominantSignature = is_string($dominant['signature'] ?? null) ? $dominant['signature'] : null;
        $mostFrequentResult = $this->dominantNormalizer->normalize($endings, $dominantSignature) ?? $referenceResult;
        $total = count($endings);
        $dominantCount = (int) $dominant['count'];
        $dominantRatio = $total > 0 ? round($dominantCount / $total, 4) : 0.0;

        // Qafya multiplicity is primarily a rawi-level question.
        // Component variants stay visible in endings/defects/sanad, but they do
        // not split the public poem-level qafya unless the rawi itself changes.
        $hasMultipleQafyas = count($clusters) > 1;
        $dominantIsAuthoritative = $dominantRatio >= self::AUTHORITATIVE_DOMINANT_RATIO;
        $isConsistent = ! $hasMultipleQafyas && $dominantRatio >= 1.0;
        $authoritativeResult = $dominantIsAuthoritative ? $mostFrequentResult : null;
        $indexing = $this->presenter->indexing($mostFrequentResult, $dominant, $dominantRatio, $dominantIsAuthoritative);

        foreach ($endings as &$ending) {
            $endingResult = is_array($ending['result'] ?? null) ? $ending['result'] : [];
            $ending['matches_reference'] = $this->clusterAnalyzer->rawiClusterSignature($endingResult) === $this->clusterAnalyzer->rawiClusterSignature($referenceResult);
            $ending['violations'] = array_values(array_filter($defects, static fn (array $defect): bool => ($defect['bayt'] ?? null) === ($ending['bayt'] ?? null)));
        }
        unset($ending);

        $hasBlockingDefects = $this->clusterAnalyzer->hasBlockingDefects($defects);

        $status = $hasMultipleQafyas || $hasBlockingDefects
            ? 'review'
            : 'ok';

        return [
            'status' => $status,
            'error' => null,
            'input' => [
                'type' => 'paired_hemistichs',
                'lines_count' => count($lines),
                'bayt_count' => count($pairs),
                'analyzed_positions' => $options->analyzeSadr ? ['sadr', 'ajz'] : ['ajz'],
            ],
            'reference' => [
                'source' => 'first_valid_ending',
                'bayt' => $reference['bayt'],
                'position' => $reference['position'],
                'ending' => $reference['text'],
                'signature' => $referenceResult['signatures']['strict'] ?? null,
                'segment' => $referenceResult['segment']['surface'] ?? null,
                'pattern' => $referenceResult['pattern']['surface'] ?? null,
            ],
            'summary' => [
                'qafya_category' => is_array($authoritativeResult) ? ($authoritativeResult['qafya']['category'] ?? null) : null,
                'qafya_subtype' => is_array($authoritativeResult) ? ($authoritativeResult['qafya']['subtype'] ?? null) : null,
                'qafya_name' => is_array($authoritativeResult) ? ($authoritativeResult['qafya']['name'] ?? null) : null,
                'qafya_segment' => is_array($authoritativeResult) ? ($authoritativeResult['segment']['surface'] ?? null) : null,
                'qafya_pattern' => is_array($authoritativeResult) ? ($authoritativeResult['pattern']['surface'] ?? null) : null,
                'is_consistent' => $isConsistent,
                'has_multiple_qafyas' => $hasMultipleQafyas,
                'dominant_is_authoritative' => $dominantIsAuthoritative,
                'dominant_ratio' => $dominantRatio,
                'confidence' => $this->presenter->averageConfidence($endings),
                'defects_count' => count($defects),
                'sanad_count' => count($sanad),
            ],
            'dominant' => is_array($authoritativeResult) ? $this->presenter->dominantPayload($authoritativeResult) : null,
            'obligations' => $obligations,
            'indexing' => $indexing,
            'clusters' => $clusters,
            'defects' => $defects,
            'sanad' => $sanad,
            'endings' => $endings,
            'diagnostics' => [
                'mode' => $options->mode,
                'notes' => array_values(array_unique(array_merge(
                    $this->clusterAnalyzer->notes($endings, $clusters, $dominantRatio, $dominantIsAuthoritative),
                    $defects !== [] && ! $hasBlockingDefects
                        ? ['non_blocking_component_variants_detected']
                        : [],
                ))),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function empty(string $error, int $linesCount): array
    {
        return [
            'status' => 'partial',
            'error' => $error,
            'input' => ['lines_count' => $linesCount, 'bayt_count' => 0],
            'summary' => [
                'is_consistent' => false,
                'has_multiple_qafyas' => false,
                'dominant_is_authoritative' => false,
                'dominant_ratio' => 0.0,
            ],
            'reference' => null,
            'dominant' => null,
            'obligations' => [],
            'indexing' => [],
            'clusters' => [],
            'defects' => [],
            'sanad' => [],
            'endings' => [],
            'diagnostics' => [],
        ];
    }
}
