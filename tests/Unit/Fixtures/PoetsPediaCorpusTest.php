<?php

declare(strict_types=1);

it('uses every PoetsPedia poem fixture as a corpus smoke test', function (): void {
    $detector = qafya_detector();
    $failures = [];

    foreach (poetspedia_poem_fixtures() as $fixture) {
        $analysis = $detector->analyze($fixture['lines']);

        if (! in_array($analysis->status(), ['ok', 'review'], true)
            || $analysis->endingsCount() < 1
            || $analysis->reference === null
            || $analysis->qafyaSegment() === null
            || $analysis->qafyaPattern() === null
        ) {
            $failures[] = [
                'id' => $fixture['id'],
                'status' => $analysis->status(),
                'error' => $analysis->error(),
                'endings' => $analysis->endingsCount(),
                'segment' => $analysis->qafyaSegment(),
                'pattern' => $analysis->qafyaPattern(),
            ];
        }
    }

    expect($failures)->toBe([]);
});

it('matches the expected rawi for every PoetsPedia fixture', function (): void {
    $detector = qafya_detector();
    $failures = [];

    foreach (poetspedia_poem_fixtures() as $fixture) {
        $analysis = $detector->analyze($fixture['lines']);
        $expectedRawi = $fixture['expected']['rawi'];
        $actualRawi = $analysis->rawi();

        if ($actualRawi !== $expectedRawi) {
            $failures[] = [
                'id' => $fixture['id'],
                'source' => $fixture['source'],
                'ending' => $fixture['lines'][1] ?? null,
                'expected_rawi' => $expectedRawi,
                'actual_rawi' => $actualRawi,
                'signature' => $analysis->dominant->signature,
                'segment' => $analysis->qafyaSegment(),
                'pattern' => $analysis->qafyaPattern(),
                'status' => $analysis->status(),
            ];
        }
    }

    expect($failures)->toBe([]);
});

it('regresses h-ending fixtures where final haa is not always the rawi', function (): void {
    $detector = qafya_detector();
    $hEndingFixtures = array_values(array_filter(
        poetspedia_poem_fixtures(),
        static fn (array $fixture): bool => str_contains((string) $fixture['source'], 'rhyme-haa')
    ));

    expect($hEndingFixtures)->not->toBeEmpty();

    $failures = [];
    foreach ($hEndingFixtures as $fixture) {
        $analysis = $detector->analyze($fixture['lines']);
        if ($analysis->rawi() !== $fixture['expected']['rawi']) {
            $failures[] = [
                'id' => $fixture['id'],
                'expected_rawi' => $fixture['expected']['rawi'],
                'actual_rawi' => $analysis->rawi(),
                'ending' => $fixture['lines'][1] ?? null,
                'trace_signature' => $analysis->dominant->signature,
            ];
        }
    }

    expect($failures)->toBe([]);
});
