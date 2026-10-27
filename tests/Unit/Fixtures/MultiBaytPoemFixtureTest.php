<?php

declare(strict_types=1);

/**
 * @return list<array<string, mixed>>
 */
function multi_bayt_poem_fixtures(): array
{
    /** @var list<array<string, mixed>> $poems */
    $poems = load_fixture('poems/multi-bayt-regression.json')['poems'];

    return $poems;
}

it('keeps the multi-bayt fixture well formed', function (): void {
    $ids = [];

    foreach (multi_bayt_poem_fixtures() as $case) {
        expect($case)->toHaveKeys(['id', 'lines', 'endings', 'expected', 'word_level', 'review_status'])
            ->and($ids)->not->toContain($case['id'])
            ->and($case['review_status'])->toBe('unreviewed')
            ->and(count($case['lines']))->toBe(count($case['endings']) * 2)
            ->and(count($case['word_level']))->toBe(count($case['endings']));

        $ids[] = $case['id'];
    }

    expect(count($ids))->toBeGreaterThanOrEqual(10);
});

it('builds every multi-bayt poem from endings that detect as documented', function (): void {
    foreach (multi_bayt_poem_fixtures() as $case) {
        foreach ($case['endings'] as $index => $ending) {
            $result = qafya_detector()->extract($ending);
            $expected = $case['word_level'][$index];

            expect($result->rawi())->toBe($expected['rawi'], "poem={$case['id']} ending={$index}")
                ->and($result->radf())->toBe($expected['radf'], "poem={$case['id']} ending={$index}")
                ->and($result->wasl())->toBe($expected['wasl'], "poem={$case['id']} ending={$index}")
                ->and($result->mujra())->toBe($expected['mujra'], "poem={$case['id']} ending={$index}");
        }
    }
});

it('analyses every multi-bayt poem as fixtured', function (): void {
    foreach (multi_bayt_poem_fixtures() as $case) {
        $expected = $case['expected'];
        $id = $case['id'];
        $analysis = qafya_detector()->analyze($case['lines']);

        expect($analysis->status())->toBe($expected['status'], "poem={$id} status")
            ->and($analysis->rawi())->toBe($expected['rawi'], "poem={$id} rawi")
            ->and($analysis->mostFrequentRawi())->toBe($expected['most_frequent_rawi'], "poem={$id} most frequent rawi")
            ->and($analysis->isConsistent())->toBe($expected['consistent'], "poem={$id} consistent")
            ->and($analysis->hasMultipleQafyas())->toBe($expected['multiple'], "poem={$id} multiple")
            ->and($analysis->dominantIsAuthoritative())->toBe($expected['authoritative'], "poem={$id} authoritative")
            ->and($analysis->usableForFiltering())->toBe($expected['usable_for_filtering'], "poem={$id} filtering")
            ->and($analysis->dominantRatio())->toBe($expected['ratio'], "poem={$id} ratio")
            ->and($analysis->baytCount())->toBe($expected['bayt_count'], "poem={$id} bayt count")
            ->and($analysis->endingsCount())->toBe($expected['bayt_count'], "poem={$id} endings count")
            ->and($analysis->reference?->source)->toBe($expected['reference_source'], "poem={$id} reference source")
            ->and($analysis->reference?->bayt)->toBe($expected['reference_bayt'], "poem={$id} reference bayt");
    }
});

it('reports exactly the fixtured defects for every multi-bayt poem', function (): void {
    foreach (multi_bayt_poem_fixtures() as $case) {
        $expected = $case['expected']['defects'];
        $id = $case['id'];
        $analysis = qafya_detector()->analyze($case['lines']);

        $total = 0;
        $sanad = 0;

        foreach ($expected as $type => $bayts) {
            $actual = array_map(
                static fn (array $defect): int => (int) $defect['bayt'],
                $analysis->defectsByType($type),
            );

            expect($actual)->toBe($bayts, "poem={$id} defect={$type}");

            $total += count($bayts);
            $sanad += str_starts_with($type, 'sanad_') ? count($bayts) : 0;
        }

        expect(count($analysis->defects()))->toBe($total, "poem={$id} total defects")
            ->and($analysis->hasDefects())->toBe($total > 0, "poem={$id} hasDefects")
            ->and(count($analysis->sanad()))->toBe($sanad, "poem={$id} sanad count");
    }
});

it('marks non-blocking variants and multiple clusters in the poem diagnostics', function (): void {
    foreach (multi_bayt_poem_fixtures() as $case) {
        $expected = $case['expected'];
        $notes = qafya_detector()->analyze($case['lines'])->diagnostics['notes'] ?? [];
        $blocking = isset($expected['defects']['rawi_mismatch']);
        $hasDefects = $expected['defects'] !== [];

        if ($expected['multiple']) {
            expect($notes, "poem={$case['id']}")->toContain('multiple_qafya_clusters_detected');
        } else {
            expect($notes, "poem={$case['id']}")->not->toContain('multiple_qafya_clusters_detected');
        }

        if ($hasDefects && ! $blocking) {
            expect($notes, "poem={$case['id']}")->toContain('non_blocking_component_variants_detected');
        } else {
            expect($notes, "poem={$case['id']}")->not->toContain('non_blocking_component_variants_detected');
        }
    }
});

it('does not flag defects when defect detection is disabled', function (): void {
    foreach (multi_bayt_poem_fixtures() as $case) {
        $analysis = qafya_detector()->analyze($case['lines'], ['detect_defects' => false]);

        expect($analysis->defects())->toBe([], "poem={$case['id']}")
            ->and($analysis->sanad())->toBe([], "poem={$case['id']}");
    }
});

it('keeps sanad out of the result when only sanad detection is disabled', function (): void {
    $case = array_values(array_filter(
        multi_bayt_poem_fixtures(),
        static fn (array $poem): bool => $poem['id'] === 'sanad-radf-alif-vs-waw',
    ))[0];

    $analysis = qafya_detector()->analyze($case['lines'], ['detect_sanad' => false]);

    expect($analysis->defectsByType('sanad_radf'))->not->toBeEmpty()
        ->and($analysis->sanad())->toBe([]);
});
