<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use Mosab\QafyaDetector\QafyaDetector;

$fixturePath = __DIR__.'/../tests/Fixtures/poems/poetspedia-50.json';

if (! is_file($fixturePath)) {
    fwrite(STDERR, "Fixture file not found: {$fixturePath}\n");
    exit(1);
}

$fixtures = json_decode(
    (string) file_get_contents($fixturePath),
    true,
    flags: JSON_THROW_ON_ERROR
);

if (! is_array($fixtures) || $fixtures === []) {
    fwrite(STDERR, "Fixture file is empty or invalid.\n");
    exit(1);
}

$detector = QafyaDetector::make();
$iterations = (int) ($argv[1] ?? 100);

if ($iterations <= 0) {
    fwrite(STDERR, "Iterations must be greater than zero.\n");
    exit(1);
}

/**
 * Warmup.
 */
foreach ($fixtures as $fixture) {
    if (! isset($fixture['lines']) || ! is_array($fixture['lines'])) {
        fwrite(STDERR, "Invalid fixture: missing lines array.\n");
        exit(1);
    }

    $detector->analyze($fixture['lines']);
}

gc_collect_cycles();

$startMemory = memory_get_usage(true);
$startPeak = memory_get_peak_usage(true);
$start = hrtime(true);

for ($i = 0; $i < $iterations; $i++) {
    foreach ($fixtures as $fixture) {
        $detector->analyze($fixture['lines']);
    }
}

$elapsedNs = hrtime(true) - $start;

$totalPoems = $iterations * count($fixtures);
$totalMs = $elapsedNs / 1_000_000;
$microsecondsPerPoem = ($elapsedNs / 1_000) / $totalPoems;
$poemsPerSecond = $totalPoems / ($elapsedNs / 1_000_000_000);

$endMemory = memory_get_usage(true);
$endPeak = memory_get_peak_usage(true);

printf("Fixtures: %d poems\n", count($fixtures));
printf("Iterations: %d\n", $iterations);
printf("Analyzed: %d poems\n", $totalPoems);
printf("Total time: %.3f ms\n", $totalMs);
printf("Average: %.2f µs/poem\n", $microsecondsPerPoem);
printf("Throughput: %.2f poems/sec\n", $poemsPerSecond);
printf("Memory diff: %.2f KB\n", ($endMemory - $startMemory) / 1024);
printf("Peak memory diff: %.2f KB\n", ($endPeak - $startPeak) / 1024);
