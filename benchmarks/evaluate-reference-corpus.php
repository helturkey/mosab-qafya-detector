<?php

declare(strict_types=1);

use Mosab\QafyaDetector\QafyaDetector;
use Mosab\QafyaDetector\Support\ReferenceCorpusValidator;

$repoRoot = dirname(__DIR__);
require $repoRoot.'/vendor/autoload.php';
$corpusPath = $argv[1] ?? ($repoRoot.'/fixtures/reference-corpus/reference-corpus-v1.json');
$split = $argv[2] ?? 'test';
$predictionsPath = $argv[3] ?? null;
if (! in_array($split, ['train', 'validation', 'test', 'holdout'], true)) {
    fwrite(STDERR, "Split must be train, validation, test, or holdout.\n");
    exit(2);
}
$json = is_file($corpusPath) ? file_get_contents($corpusPath) : false;
$data = is_string($json) ? json_decode($json, true) : null;
if (! is_array($data)) {
    fwrite(STDERR, "Cannot read valid JSON corpus: {$corpusPath}\n");
    exit(2);
}
$errors = (new ReferenceCorpusValidator)->validate($data);
if ($errors !== []) {
    fwrite(STDERR, "Reference corpus invalid:\n - ".implode("\n - ", $errors)."\n");
    exit(2);
}

$eligibleRecords = array_values(array_filter(
    $data['records'] ?? [],
    static fn ($record): bool => is_array($record)
        && ($record['review']['status'] ?? null) === 'verified'
        && ($record['split'] ?? null) === $split
));
if ($eligibleRecords === []) {
    $output = [
        'schema_version' => 1,
        'benchmark' => 'mosab-qafya-reference-corpus',
        'split' => $split,
        'records_evaluated' => 0,
        'metrics' => [],
        'exact_match_accuracy' => null,
        'status' => 'no_verified_records_for_split',
        'note' => 'Only human-verified records are scored. Seed regression records are unreviewed and intentionally excluded.',
    ];
    if (is_string($predictionsPath) && $predictionsPath !== '') {
        if (is_file($predictionsPath) && file_get_contents($predictionsPath) !== "[]\n") {
            fwrite(STDERR, "Refusing to overwrite a different predictions file: {$predictionsPath}.\n");
            exit(3);
        }
        if (file_put_contents($predictionsPath, "[]\n") === false) {
            fwrite(STDERR, "Could not write empty confidence predictions to {$predictionsPath}.\n");
            exit(2);
        }
        $output['predictions_written_to'] = $predictionsPath;
    }
    echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n";
    exit(0);
}

$map = [
    'rawi' => static fn ($r): ?string => $r->rawi(),
    'rawi_haraka' => static fn ($r): ?string => $r->rawiHaraka(),
    'radf' => static fn ($r): ?string => $r->radf(),
    'wasl' => static fn ($r): ?string => $r->wasl(),
    'khurooj' => static fn ($r): ?string => $r->khurooj(),
    'taasis' => static fn ($r): ?string => $r->taasis(),
    'dakhiil' => static fn ($r): ?string => $r->dakhiil(),
    'segment_surface' => static fn ($r): ?string => $r->segmentSurface(),
    'pattern_surface' => static fn ($r): ?string => $r->patternSurface(),
];
$metrics = [];
$evaluated = 0;
$predictions = [];
$detector = QafyaDetector::make();
foreach ($eligibleRecords as $record) {
    $options = [
        'mode' => is_string($record['mode'] ?? null) ? $record['mode'] : 'auto',
        'strict_harakat' => true,
    ];
    if (($record['kind'] ?? null) === 'poem') {
        $analysis = $detector->analyzeArray((string) $record['input'], $options);
        $dominant = is_array($analysis['dominant'] ?? null) ? $analysis['dominant'] : [];
        $actual = [
            'rawi' => $dominant['rawi'] ?? null,
            'rawi_haraka' => $dominant['rawi_haraka'] ?? null,
            'radf' => $dominant['radf'] ?? null,
            'wasl' => $dominant['wasl'] ?? null,
            'khurooj' => $dominant['khurooj'] ?? null,
            'taasis' => $dominant['taasis'] ?? null,
            'dakhiil' => $dominant['dakhiil'] ?? null,
            'segment_surface' => $dominant['segment'] ?? null,
            'pattern_surface' => $dominant['pattern'] ?? null,
        ];
        $confidence = (float) ($analysis['summary']['confidence'] ?? 0.0);
    } else {
        $result = $detector->extract((string) $record['input'], $options);
        $actual = [];
        foreach ($map as $field => $getter) {
            $actual[$field] = $getter($result);
        }
        $confidence = $result->confidenceScore();
    }

    $exact = true;
    foreach ($map as $field => $_getter) {
        if (($actual[$field] ?? null) !== ($record['expected'][$field] ?? null)) {
            $exact = false;
        }
        $metrics[$field] ??= ['correct' => 0, 'total' => 0];
        $metrics[$field]['total']++;
        if (($actual[$field] ?? null) === ($record['expected'][$field] ?? null)) {
            $metrics[$field]['correct']++;
        }
    }
    $evaluated++;
    $predictions[] = [
        'record_id' => $record['id'],
        'split' => $split,
        'review_status' => 'verified',
        'confidence' => max(0.0, min(1.0, $confidence)),
        'correct' => $exact,
        'score_semantics' => 'raw_rule_based_evidence_score_until_calibration_is_validated',
    ];
}

foreach ($metrics as $field => &$metric) {
    $metric['accuracy'] = $metric['total'] > 0 ? round($metric['correct'] / $metric['total'], 4) : null;
}
unset($metric);
$output = [
    'schema_version' => 1,
    'benchmark' => 'mosab-qafya-reference-corpus',
    'split' => $split,
    'records_evaluated' => $evaluated,
    'metrics' => $metrics,
    'exact_match_accuracy' => $evaluated > 0
        ? round(count(array_filter($predictions, static fn (array $p): bool => $p['correct'])) / $evaluated, 4)
        : null,
    'status' => $evaluated > 0 ? 'evaluated' : 'no_verified_records_for_split',
    'note' => 'Only human-verified records are scored. Seed regression records are unreviewed and intentionally excluded.',
];
if (is_string($predictionsPath) && $predictionsPath !== '') {
    $serialized = json_encode($predictions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($serialized === false || file_put_contents($predictionsPath, $serialized."\n") === false) {
        fwrite(STDERR, "Could not write confidence predictions to {$predictionsPath}.\n");
        exit(2);
    }
    $output['predictions_written_to'] = $predictionsPath;
}
echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n";
