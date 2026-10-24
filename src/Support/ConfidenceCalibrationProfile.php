<?php

declare(strict_types=1);

namespace Mosab\QafyaDetector\Support;

/**
 * Applies an empirical score-to-accuracy table only after it passed an
 * independent holdout check. Candidate profiles must never affect runtime
 * output as if they were production-calibrated.
 */
final readonly class ConfidenceCalibrationProfile
{
    /** @param array<string, mixed> $profile */
    public function __construct(private array $profile) {}

    public function calibrate(float $rawScore): ?float
    {
        if (($this->profile['schema_version'] ?? null) !== 1
            || ($this->profile['status'] ?? null) !== 'validated_on_holdout'
            || ($this->profile['fit_split'] ?? null) !== 'validation'
            || (int) ($this->profile['sample_count'] ?? 0) < 100) {
            return null;
        }

        $evaluation = $this->profile['holdout_evaluation'] ?? null;
        if (! is_array($evaluation)
            || (int) ($evaluation['test_sample_count'] ?? 0) < 100
            || ! is_numeric($evaluation['coverage'] ?? null)
            || (float) $evaluation['coverage'] < 0.80
            || ($evaluation['improves_raw_baseline'] ?? false) !== true
            || ! is_string($this->profile['source_predictions_sha256'] ?? null)
            || preg_match('/^[a-f0-9]{64}$/', $this->profile['source_predictions_sha256']) !== 1
            || ! is_string($evaluation['test_predictions_sha256'] ?? null)
            || preg_match('/^[a-f0-9]{64}$/', $evaluation['test_predictions_sha256']) !== 1
            || ! is_numeric($evaluation['brier_score_on_mapped_test_records'] ?? null)
            || ! is_numeric($evaluation['raw_brier_score_on_same_mapped_records'] ?? null)
            || (float) $evaluation['brier_score_on_mapped_test_records'] >= (float) $evaluation['raw_brier_score_on_same_mapped_records']) {
            return null;
        }

        $bins = $this->profile['bins'] ?? null;
        if (! is_array($bins) || count($bins) < 3) {
            return null;
        }

        $qualifiedBins = 0;
        $previousUpper = 0.0;
        $previousAccuracy = -1.0;
        foreach ($bins as $bin) {
            if (! is_array($bin)
                || ! is_numeric($bin['count'] ?? null)
                || (int) $bin['count'] < 10
                || ! is_numeric($bin['lower_bound'] ?? null)
                || ! is_numeric($bin['upper_bound'] ?? null)
                || ! is_numeric($bin['accuracy'] ?? null)) {
                return null;
            }
            $lower = (float) $bin['lower_bound'];
            $upper = (float) $bin['upper_bound'];
            $accuracy = (float) $bin['accuracy'];
            if ($lower < 0.0 || $upper > 1.0 || $lower >= $upper || $lower < $previousUpper
                || $accuracy < 0.0 || $accuracy > 1.0 || $accuracy < $previousAccuracy) {
                return null;
            }
            $previousUpper = $upper;
            $previousAccuracy = $accuracy;
            $qualifiedBins++;
        }
        if ($qualifiedBins < 3) {
            return null;
        }

        $rawScore = max(0.0, min(1.0, $rawScore));
        $previousUpper = 0.0;
        foreach ($bins as $bin) {
            if (! is_numeric($bin['upper_bound'] ?? null)) {
                continue;
            }
            $upper = (float) $bin['upper_bound'];
            $lower = is_numeric($bin['lower_bound'] ?? null) ? (float) $bin['lower_bound'] : $previousUpper;
            $previousUpper = $upper;
            $count = $bin['count'] ?? null;
            $accuracy = $bin['accuracy'] ?? null;
            if (! is_numeric($count) || ! is_numeric($accuracy) || (int) $count < 10) {
                continue;
            }
            if ($rawScore >= $lower && $rawScore <= $upper) {
                return max(0.0, min(1.0, (float) $accuracy));
            }
        }

        return null;
    }

    public function id(): ?string
    {
        $id = $this->profile['profile_id'] ?? null;

        return is_string($id) ? $id : null;
    }
}
