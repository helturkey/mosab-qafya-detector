<?php

declare(strict_types=1);

/**
 * Diagnose raw scores or evaluate a validation-fitted calibration profile on
 * a held-out test split. Never fits a profile on test records.
 */
$inputPath = $argv[1] ?? null;
if (! is_string($inputPath) || ! is_file($inputPath)) {
    fwrite(STDERR, "Usage: php benchmarks/diagnose-confidence.php predictions.json [--write-profile=candidate.json] [--profile=candidate.json] [--promote-profile=validated.json]\n");
    exit(2);
}
$options = [];
foreach (array_slice($argv, 2) as $arg) {
    if (str_starts_with($arg, '--write-profile=')) {
        $options['write_profile'] = substr($arg, strlen('--write-profile='));
    } elseif (str_starts_with($arg, '--profile=')) {
        $options['profile'] = substr($arg, strlen('--profile='));
    } elseif (str_starts_with($arg, '--promote-profile=')) {
        $options['promote_profile'] = substr($arg, strlen('--promote-profile='));
    }
}
$decoded = json_decode((string) file_get_contents($inputPath), true);
if (! is_array($decoded) || ! array_is_list($decoded)) {
    fwrite(STDERR, "Predictions file must contain a JSON list.\n");
    exit(2);
}
$predictions = array_values(array_filter($decoded, static fn ($r): bool => is_array($r)
    && ($r['review_status'] ?? null) === 'verified'
    && is_numeric($r['confidence'] ?? null)
    && is_bool($r['correct'] ?? null)
));
if (isset($options['promote_profile']) && ! isset($options['profile'])) {
    fwrite(STDERR, "--promote-profile requires --profile pointing to the validation-fitted candidate profile.\n");
    exit(2);
}
$split = count($predictions) > 0 ? ($predictions[0]['split'] ?? null) : null;
if (! in_array($split, ['validation', 'test'], true)) {
    fwrite(STDERR, "Predictions must all be verified validation or test records.\n");
    exit(2);
}
foreach ($predictions as $row) {
    if (($row['split'] ?? null) !== $split) {
        fwrite(STDERR, "Do not mix validation and test predictions in one file.\n");
        exit(2);
    }
}

$rawScores = array_map(static fn (array $r): float => max(0.0, min(1.0, (float) $r['confidence'])), $predictions);
$correct = array_map(static fn (array $r): int => $r['correct'] ? 1 : 0, $predictions);
$count = count($predictions);
$binCount = 10;
$bins = [];
for ($i = 0; $i < $binCount; $i++) {
    $bins[$i] = ['lower_bound' => $i / $binCount, 'upper_bound' => ($i + 1) / $binCount, 'scores' => [], 'correct' => 0];
}
foreach ($predictions as $row) {
    $score = max(0.0, min(1.0, (float) $row['confidence']));
    $binIndex = min($binCount - 1, (int) floor($score * $binCount));
    $bins[$binIndex]['scores'][] = $score;
    $bins[$binIndex]['correct'] += $row['correct'] ? 1 : 0;
}
$renderedBins = [];
$ece = 0.0;
$populated = 0;
foreach ($bins as $bin) {
    $n = count($bin['scores']);
    if ($n === 0) {
        continue;
    }
    $meanScore = array_sum($bin['scores']) / $n;
    $accuracy = $bin['correct'] / $n;
    $ece += ($n / max(1, $count)) * abs($meanScore - $accuracy);
    if ($n >= 10) {
        $populated++;
    }
    $renderedBins[] = [
        'lower_bound' => $bin['lower_bound'],
        'upper_bound' => $bin['upper_bound'],
        'count' => $n,
        'mean_raw_score' => round($meanScore, 4),
        'accuracy' => round($accuracy, 4),
    ];
}

// Pool adjacent violators so estimated accuracy is monotone in the raw score.
// This reduces inversions caused by sampling noise in neighboring bins.
$isotonicGroups = [];
foreach ($bins as $bin) {
    $n = count($bin['scores']);
    if ($n === 0) {
        continue;
    }
    $isotonicGroups[] = [
        'lower_bound' => $bin['lower_bound'],
        'upper_bound' => $bin['upper_bound'],
        'count' => $n,
        'correct' => $bin['correct'],
        'score_sum' => array_sum($bin['scores']),
    ];
    while (count($isotonicGroups) >= 2) {
        $lastIndex = count($isotonicGroups) - 1;
        $left = $isotonicGroups[$lastIndex - 1];
        $right = $isotonicGroups[$lastIndex];
        $leftAccuracy = $left['correct'] / $left['count'];
        $rightAccuracy = $right['correct'] / $right['count'];
        if ($leftAccuracy <= $rightAccuracy) {
            break;
        }
        array_pop($isotonicGroups);
        array_pop($isotonicGroups);
        $isotonicGroups[] = [
            'lower_bound' => $left['lower_bound'],
            'upper_bound' => $right['upper_bound'],
            'count' => $left['count'] + $right['count'],
            'correct' => $left['correct'] + $right['correct'],
            'score_sum' => $left['score_sum'] + $right['score_sum'],
        ];
    }
}
$profileBins = [];
foreach ($isotonicGroups as $group) {
    if ($group['count'] < 10) {
        continue;
    }
    $profileBins[] = [
        'lower_bound' => $group['lower_bound'],
        'upper_bound' => $group['upper_bound'],
        'count' => $group['count'],
        'accuracy' => round($group['correct'] / $group['count'], 6),
        'mean_raw_score' => round($group['score_sum'] / $group['count'], 6),
    ];
}
$canFit = $split === 'validation' && $count >= 100 && count($profileBins) >= 3;
$output = [
    'schema_version' => 1,
    'split' => $split,
    'sample_count' => $count,
    'raw_score_semantics' => 'rule_based_evidence_score_not_a_probability',
    'raw_score_diagnostic_ece' => $count > 0 ? round($ece, 4) : null,
    'minimum_samples_for_candidate_profile' => 100,
    'candidate_profile_ready' => $canFit,
    'bins' => $renderedBins,
    'note' => 'Raw-score ECE is diagnostic only; an empirical mapping must be tested on a separate held-out test split before production use.',
];

if (isset($options['write_profile'])) {
    if (! $canFit) {
        fwrite(STDERR, "Not writing a calibration profile: use at least 100 verified validation predictions and at least 3 score bins with 10 or more samples each.\n");
        exit(3);
    }
    $profile = [
        'schema_version' => 1,
        'profile_id' => 'mosab-confidence-'.substr(hash_file('sha256', $inputPath) ?: hash('sha256', (string) $count), 0, 12),
        'status' => 'ready_for_holdout_test',
        'fit_split' => 'validation',
        'sample_count' => $count,
        'source_predictions_sha256' => hash_file('sha256', $inputPath),
        'method' => 'equal_width_binning_with_pool_adjacent_violators_isotonic_mapping',
        'minimum_bin_count' => 10,
        'bins' => $profileBins,
        'warning' => 'Not approved for production until evaluated on independent test predictions.',
    ];
    $serialized = json_encode($profile, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($serialized === false || file_put_contents($options['write_profile'], $serialized."\n") === false) {
        fwrite(STDERR, "Could not write profile file.\n");
        exit(2);
    }
    $output['profile_written_to'] = $options['write_profile'];
}

if (isset($options['profile'])) {
    if ($split !== 'test') {
        fwrite(STDERR, "A calibration profile must be evaluated against test-split predictions, never validation predictions.\n");
        exit(3);
    }
    $profileData = is_file($options['profile']) ? json_decode((string) file_get_contents($options['profile']), true) : null;
    if (! is_array($profileData)
        || ($profileData['schema_version'] ?? null) !== 1
        || ($profileData['fit_split'] ?? null) !== 'validation'
        || ($profileData['status'] ?? null) !== 'ready_for_holdout_test'
        || (int) ($profileData['sample_count'] ?? 0) < 100) {
        fwrite(STDERR, "Profile must be a schema-v1 validation-fitted candidate with at least 100 reviewed samples.\n");
        exit(2);
    }
    $sumSquared = 0.0;
    $rawSumSquaredOnMapped = 0.0;
    $calibratedPredictions = [];
    foreach ($predictions as $row) {
        $score = max(0.0, min(1.0, (float) $row['confidence']));
        $calibrated = null;
        $previousUpper = 0.0;
        foreach (($profileData['bins'] ?? []) as $bin) {
            if (! is_array($bin) || ! is_numeric($bin['upper_bound'] ?? null)) {
                continue;
            }
            $upper = (float) $bin['upper_bound'];
            $lower = is_numeric($bin['lower_bound'] ?? null) ? (float) $bin['lower_bound'] : $previousUpper;
            $previousUpper = $upper;
            if (is_numeric($bin['accuracy'] ?? null) && (int) ($bin['count'] ?? 0) >= 10
                && $score >= $lower && $score <= $upper) {
                $calibrated = max(0.0, min(1.0, (float) $bin['accuracy']));
                break;
            }
        }
        if ($calibrated === null) {
            continue;
        }
        $target = $row['correct'] ? 1.0 : 0.0;
        $sumSquared += ($calibrated - $target) ** 2;
        $rawSumSquaredOnMapped += ($score - $target) ** 2;
        $calibratedPredictions[] = ['score' => $calibrated, 'correct' => $target];
    }
    $calibratedCount = count($calibratedPredictions);
    $coverage = $count > 0 ? round($calibratedCount / $count, 4) : 0.0;
    $calibratedBrier = $calibratedCount > 0 ? round($sumSquared / $calibratedCount, 6) : null;
    $rawBrierSameRows = $calibratedCount > 0 ? round($rawSumSquaredOnMapped / $calibratedCount, 6) : null;
    $improves = $calibratedBrier !== null && $rawBrierSameRows !== null && $calibratedBrier < $rawBrierSameRows;
    $evaluation = [
        'profile_id' => $profileData['profile_id'] ?? null,
        'test_sample_count' => $count,
        'scores_mapped' => $calibratedCount,
        'coverage' => $coverage,
        'brier_score_on_mapped_test_records' => $calibratedBrier,
        'raw_brier_score_on_same_mapped_records' => $rawBrierSameRows,
        'improves_raw_baseline' => $improves,
        'interpretation' => 'Promotion requires at least 100 independent test records, coverage >= 0.80, and lower mapped Brier score than the raw-score baseline on the same records.',
    ];
    $output['heldout_calibration_evaluation'] = $evaluation;

    if (isset($options['promote_profile'])) {
        if ($count < 100 || $calibratedCount / max(1, $count) < 0.80 || ! $improves
            || ($profileData['status'] ?? null) !== 'ready_for_holdout_test'
            || ($profileData['fit_split'] ?? null) !== 'validation') {
            fwrite(STDERR, "Calibration profile not promoted: require a validation-fitted candidate, 100+ independent test predictions, >=80% coverage, and an improved Brier score on the same mapped records.\n");
            exit(3);
        }
        $approvedProfile = $profileData;
        $approvedProfile['status'] = 'validated_on_holdout';
        $approvedProfile['holdout_evaluation'] = $evaluation;
        $approvedProfile['holdout_evaluation']['test_predictions_sha256'] = hash_file('sha256', $inputPath);
        $approvedProfile['warning'] = 'Passed the configured holdout gate; continue monitoring and periodically revalidate on new independently reviewed samples.';
        $serialized = json_encode($approvedProfile, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (is_file($options['promote_profile']) && file_get_contents($options['promote_profile']) !== $serialized."\n") {
            fwrite(STDERR, "Refusing to overwrite a different existing profile: {$options['promote_profile']}.\n");
            exit(3);
        }
        if ($serialized === false || file_put_contents($options['promote_profile'], $serialized."\n") === false) {
            fwrite(STDERR, "Could not write validated profile.\n");
            exit(2);
        }
        $output['validated_profile_written_to'] = $options['promote_profile'];
    }
}

echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n";
