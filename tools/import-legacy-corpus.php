<?php

declare(strict_types=1);

$inputPath = $argv[1] ?? null;
$outputPath = $argv[2] ?? null;
if (! is_string($inputPath) || ! is_file($inputPath) || ! is_string($outputPath) || $outputPath === '') {
    fwrite(STDERR, "Usage: php tools/import-legacy-corpus.php legacy-poems.json fixtures/reference-corpus/legacy-import-v1.json\n");
    exit(2);
}
$decoded = json_decode((string) file_get_contents($inputPath), true);
if (! is_array($decoded) || ! array_is_list($decoded)) {
    fwrite(STDERR, "Legacy file must contain a JSON list of poem records.\n");
    exit(2);
}
$records = [];
$seen = [];
foreach ($decoded as $index => $row) {
    if (! is_array($row) || ! is_string($row['id'] ?? null) || ! is_array($row['lines'] ?? null)) {
        fwrite(STDERR, "Skipping malformed legacy record at index {$index}.\n");

        continue;
    }
    $id = 'legacy-'.$row['id'];
    if (isset($seen[$id])) {
        fwrite(STDERR, "Duplicate legacy ID {$id}; stopping before write.\n");
        exit(3);
    }
    $seen[$id] = true;
    $lines = array_values(array_filter($row['lines'], 'is_string'));
    if ($lines === []) {
        continue;
    }
    $expected = [];
    if (is_array($row['expected'] ?? null) && array_key_exists('rawi', $row['expected'])) {
        $expected['rawi'] = is_string($row['expected']['rawi']) ? $row['expected']['rawi'] : null;
    }
    $records[] = [
        'id' => $id,
        'kind' => 'poem',
        'input' => implode("\n", $lines),
        'mode' => 'auto',
        'split' => 'holdout',
        'source' => [
            'type' => 'legacy_fixture_import',
            'reference' => is_string($row['source'] ?? null) ? $row['source'] : basename($inputPath),
            'locator' => 'record-id: '.$row['id'],
        ],
        'expected' => $expected,
        'review' => [
            'status' => 'unreviewed',
            'reviewer' => null,
            'reviewed_at' => null,
            'rationale' => 'Imported legacy rawi-only fixture. Component labels and scholarly source locator have not been independently verified.',
        ],
    ];
}
$corpus = [
    'schema_version' => 1,
    'metadata' => [
        'name' => 'Imported legacy rawi-only corpus',
        'purpose' => 'Candidate material for human review; not a verified component-level benchmark.',
        'input_file' => basename($inputPath),
        'records_imported' => count($records),
        'policy' => 'All rows remain unreviewed and are excluded from scoring until a human reviewer completes component labels and provenance.',
    ],
    'records' => $records,
];
$serialized = json_encode($corpus, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($serialized === false) {
    fwrite(STDERR, "Could not serialize imported corpus.\n");
    exit(2);
}
$directory = dirname($outputPath);
if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
    fwrite(STDERR, "Could not create output directory {$directory}.\n");
    exit(2);
}
if (is_file($outputPath) && file_get_contents($outputPath) !== $serialized."\n") {
    fwrite(STDERR, "Refusing to overwrite an existing, different file: {$outputPath}\n");
    exit(3);
}
if (file_put_contents($outputPath, $serialized."\n") === false) {
    fwrite(STDERR, "Could not write {$outputPath}.\n");
    exit(2);
}
printf("Imported %d legacy records as unreviewed material. They are not included in accuracy metrics.\n", count($records));
