<?php

declare(strict_types=1);

use Mosab\QafyaDetector\Analysis\PoemClusterAnalyzer;

/**
 * @return array<string, mixed>
 */
function cluster_ending(int $bayt, ?string $rawi, string $status = 'ok', bool $complete = true): array
{
    return [
        'bayt' => $bayt,
        'position' => 'ajz',
        'result' => [
            'status' => $status,
            'segment' => ['complete' => $complete],
            'qafya' => ['components' => $rawi === null ? [] : ['rawi' => ['letter' => $rawi]]],
        ],
    ];
}

it('finds the first valid ending and skips partial results', function (): void {
    $analyzer = new PoemClusterAnalyzer;
    $endings = [cluster_ending(0, null, 'partial'), cluster_ending(1, 'ب'), cluster_ending(2, 'م')];

    expect($analyzer->firstValid($endings)['bayt'])->toBe(1)
        ->and($analyzer->firstValid([cluster_ending(0, null, 'partial')]))->toBeNull()
        ->and($analyzer->firstValid([]))->toBeNull();
});

it('finds the first valid ending of a given rawi cluster', function (): void {
    $analyzer = new PoemClusterAnalyzer;
    $endings = [cluster_ending(0, 'م'), cluster_ending(1, 'ب', 'partial'), cluster_ending(2, 'ب'), cluster_ending(3, 'ب')];

    expect($analyzer->firstValidInCluster($endings, 'Rب')['bayt'])->toBe(2)
        ->and($analyzer->firstValidInCluster($endings, 'Rم')['bayt'])->toBe(0)
        ->and($analyzer->firstValidInCluster($endings, 'Rن'))->toBeNull()
        ->and($analyzer->firstValidInCluster($endings, null))->toBeNull();
});

it('builds rawi cluster signatures and canonicalizes hamza seats', function (): void {
    $analyzer = new PoemClusterAnalyzer;

    expect($analyzer->rawiClusterSignature(cluster_ending(0, 'ب')['result']))->toBe('Rب')
        ->and($analyzer->rawiClusterSignature(cluster_ending(0, 'أ')['result']))->toBe('Rء')
        ->and($analyzer->rawiClusterSignature(cluster_ending(0, 'ئ')['result']))->toBe('Rء')
        ->and($analyzer->rawiClusterSignature(cluster_ending(0, null)['result']))->toBeNull()
        ->and($analyzer->rawiClusterSignature([]))->toBeNull();
});

it('groups hamza spellings into one cluster', function (): void {
    $clusters = (new PoemClusterAnalyzer)->clusters([
        cluster_ending(0, 'ء'),
        cluster_ending(1, 'أ'),
        cluster_ending(2, 'ئ'),
    ]);

    expect($clusters)->toBe([['signature' => 'Rء', 'count' => 3, 'ratio' => 1.0]]);
});

it('counts clusters, orders them by size, and rounds ratios', function (): void {
    $clusters = (new PoemClusterAnalyzer)->clusters([
        cluster_ending(0, 'م'),
        cluster_ending(1, 'ب'),
        cluster_ending(2, 'ب'),
    ]);

    expect($clusters)->toBe([
        ['signature' => 'Rب', 'count' => 2, 'ratio' => 0.6667],
        ['signature' => 'Rم', 'count' => 1, 'ratio' => 0.3333],
    ]);
});

it('keeps endings without a rawi in an unknown cluster with a null signature', function (): void {
    $clusters = (new PoemClusterAnalyzer)->clusters([
        cluster_ending(0, 'ب'),
        cluster_ending(1, 'ب'),
        cluster_ending(2, null, 'partial'),
    ]);

    expect($clusters[0])->toBe(['signature' => 'Rب', 'count' => 2, 'ratio' => 0.6667])
        ->and($clusters[1])->toBe(['signature' => null, 'count' => 1, 'ratio' => 0.3333])
        ->and((new PoemClusterAnalyzer)->clusters([]))->toBe([]);
});

it('treats only rawi-level problems as blocking defects', function (): void {
    $analyzer = new PoemClusterAnalyzer;

    foreach (['rawi_mismatch', 'missing_rawi', 'invalid_reference', 'unresolved_reference', 'no_valid_qafya_reference'] as $type) {
        expect($analyzer->hasBlockingDefects([['type' => 'iqwa'], ['type' => $type]]))->toBeTrue($type);
    }

    foreach (['iqwa', 'israf', 'sanad_radf', 'sanad_taasis', 'wasl_mismatch', 'khurooj_mismatch', 'ita'] as $type) {
        expect($analyzer->hasBlockingDefects([['type' => $type]]))->toBeFalse($type);
    }

    expect($analyzer->hasBlockingDefects([]))->toBeFalse()
        ->and($analyzer->hasBlockingDefects([['bayt' => 1], ['type' => null]]))->toBeFalse();
});

it('adds no notes for a single authoritative cluster with complete boundaries', function (): void {
    $clusters = [['signature' => 'Rب', 'count' => 3, 'ratio' => 1.0]];

    expect((new PoemClusterAnalyzer)->notes([cluster_ending(0, 'ب')], $clusters, 1.0, true))->toBe([]);
});

it('notes estimated boundaries once even when several endings are incomplete', function (): void {
    $notes = (new PoemClusterAnalyzer)->notes(
        [cluster_ending(0, 'ب', 'ok', false), cluster_ending(1, 'ب', 'ok', false)],
        [['signature' => 'Rب', 'count' => 2, 'ratio' => 1.0]],
        1.0,
        true,
    );

    expect($notes)->toBe(['some_endings_used_estimated_boundary_because_diacritics_are_missing']);
});

it('notes a secondary cluster beside an authoritative dominant one', function (): void {
    $notes = (new PoemClusterAnalyzer)->notes(
        [cluster_ending(0, 'ب')],
        [
            ['signature' => 'Rب', 'count' => 5, 'ratio' => 0.8333],
            ['signature' => 'Rم', 'count' => 1, 'ratio' => 0.1667],
        ],
        0.8333,
        true,
    );

    expect($notes)->toBe([
        'multiple_qafya_clusters_detected',
        'dominant_cluster_is_authoritative_but_secondary_clusters_exist',
    ]);
});

it('notes a non-authoritative dominant cluster with a tie in a stable order', function (): void {
    $notes = (new PoemClusterAnalyzer)->notes(
        [cluster_ending(0, 'ب'), cluster_ending(1, 'م')],
        [
            ['signature' => 'Rب', 'count' => 1, 'ratio' => 0.5],
            ['signature' => 'Rم', 'count' => 1, 'ratio' => 0.5],
        ],
        0.5,
        false,
    );

    expect($notes)->toBe([
        'multiple_qafya_clusters_detected',
        'dominant_cluster_is_not_authoritative_use_clusters_or_endings',
        'dominant_ratio_below_authoritative_threshold',
        'dominant_cluster_tie_detected',
    ]);
});

it('does not flag a tie or a low ratio when there is no dominant ratio', function (): void {
    expect((new PoemClusterAnalyzer)->notes([], [], 0.0, false))->toBe([]);
});
