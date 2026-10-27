<?php

declare(strict_types=1);

use Mosab\QafyaDetector\Support\ArabicText;

/**
 * @return list<array<string, mixed>>
 */
function word_component_fixtures(): array
{
    /** @var list<array<string, mixed>> $words */
    $words = load_fixture('words/word-components.json')['words'];

    return $words;
}

/**
 * Every trace rule ComponentDetector can emit. A new rule must get a fixture.
 *
 * @return list<string>
 */
function all_component_rule_ids(): array
{
    return [
        'final_hamza_waw_alef',
        'haa_preceded_by_madd_with_final_madd',
        'haa_with_final_madd',
        'vocalized_taa_marbuta_as_taa_rawi',
        'final_haa_or_paused_taa_marbuta_after_alef_like',
        'final_haa_after_waw',
        'sakin_haa_after_yaa_pause',
        'vocalized_pronominal_haa_after_yaa',
        'bare_haa_pause_or_ta_marbuta',
        'bare_aya_ayh_suffix',
        'bare_pronominal_haa_after_yaa',
        'bare_haa',
        'final_maqsura_after_hamza_seat',
        'final_alef_maqsura',
        'final_alef_madda',
        'final_alef',
        'final_weak_after_alef_as_rawi',
        'final_waw_yaa',
        'extraneous_noon_tawkid',
        'extraneous_noon_niswa',
        'final_consonant',
    ];
}

it('keeps the word-components fixture well formed', function (): void {
    $ids = [];
    $keys = ['rawi', 'radf', 'taasis', 'dakhiil', 'wasl', 'khurooj'];

    foreach (word_component_fixtures() as $case) {
        expect($case)->toHaveKeys(['id', 'word', 'group', 'rule', 'category', 'expected', 'motions', 'review_status'])
            ->and($case['id'])->toBeString()->not->toBe('')
            ->and($ids)->not->toContain($case['id'])
            ->and(['expected', 'heuristic'])->toContain($case['group'])
            ->and($case['review_status'])->toBe('unreviewed')
            ->and(array_keys($case['expected']))->toBe($keys);

        $ids[] = $case['id'];
    }

    expect(count($ids))->toBeGreaterThanOrEqual(60);
});

it('matches the expected components of every word fixture', function (): void {
    foreach (word_component_fixtures() as $case) {
        $result = qafya_detector()->extract($case['word']);

        expect($result->status())->toBe('ok', "id={$case['id']}")
            ->and($result->category())->toBe($case['category'], "id={$case['id']}")
            ->and($result->rawi())->toBe($case['expected']['rawi'], "id={$case['id']} rawi")
            ->and($result->radf())->toBe($case['expected']['radf'], "id={$case['id']} radf")
            ->and($result->taasis())->toBe($case['expected']['taasis'], "id={$case['id']} taasis")
            ->and($result->dakhiil())->toBe($case['expected']['dakhiil'], "id={$case['id']} dakhiil")
            ->and($result->wasl())->toBe($case['expected']['wasl'], "id={$case['id']} wasl")
            ->and($result->khurooj())->toBe($case['expected']['khurooj'], "id={$case['id']} khurooj");
    }
});

it('matches the expected vowel motions of every word fixture', function (): void {
    foreach (word_component_fixtures() as $case) {
        expect(qafya_detector()->extract($case['word'])->motions())->toEqual($case['motions'], "id={$case['id']}");
    }
});

it('reports the expected trace rule and ambiguities for every word fixture', function (): void {
    foreach (word_component_fixtures() as $case) {
        $array = qafya_detector()->extractArray($case['word']);
        $ambiguities = array_map(
            static fn (array $item): string => (string) ($item['type'] ?? ''),
            $array['diagnostics']['ambiguities'],
        );

        expect($array['trace'][0]['rule'])->toBe($case['rule'], "id={$case['id']}")
            ->and($ambiguities)->toBe($case['ambiguous'], "id={$case['id']}");
    }
});

it('exercises every component detector rule at least once', function (): void {
    $covered = array_values(array_unique(array_map(
        static fn (array $case): string => $case['rule'],
        word_component_fixtures(),
    )));

    foreach (all_component_rule_ids() as $rule) {
        expect($covered)->toContain($rule);
    }
});

it('keeps the word fixture classical checks consistent with current behavior', function (): void {
    foreach (word_component_fixtures() as $case) {
        if ($case['group'] !== 'expected') {
            continue;
        }

        expect($case['classical_checked'])->not->toBeEmpty("id={$case['id']}");

        foreach ($case['classical_checked'] as $key) {
            expect($case['expected'])->toHaveKey($key);
        }
    }
});

it('pins the known limitations as current behavior and documents the classical reading', function (): void {
    $limitations = load_fixture('words/known-limitations.json')['words'];

    expect($limitations)->not->toBeEmpty();

    foreach ($limitations as $case) {
        expect($case)->toHaveKeys(['id', 'word', 'rule', 'classical', 'current', 'note']);

        $result = qafya_detector()->extract($case['word']);
        $array = qafya_detector()->extractArray($case['word']);

        foreach ($case['current'] as $key => $letter) {
            expect($result->{$key}())->toBe($letter, "limitation={$case['id']} key={$key}");
        }

        expect($array['trace'][0]['rule'])->toBe($case['rule'], "limitation={$case['id']}")
            ->and($case['classical'])->not->toEqual($case['current'], "limitation={$case['id']} must still differ from classical");
    }
});

it('reduces noisy input to the same ending as the plain word', function (): void {
    $data = load_fixture('words/input-normalization.json');

    expect($data['cases'])->not->toBeEmpty();

    foreach ($data['cases'] as $case) {
        $result = qafya_detector()->extract($case['input']);

        expect(ArabicText::lastArabicWord($case['input'], preserveTashkeel: true))->toBe($case['last_word'], "id={$case['id']} last word")
            ->and($result->rawi())->toBe($case['expected_rawi'], "id={$case['id']} rawi")
            ->and($result->radf())->toBe($case['expected_radf'], "id={$case['id']} radf")
            ->and($result->wasl())->toBe($case['expected_wasl'], "id={$case['id']} wasl");
    }
});

it('returns a partial result with no components when the input has no Arabic ending', function (): void {
    $data = load_fixture('words/input-normalization.json');

    expect($data['no_ending'])->not->toBeEmpty();

    foreach ($data['no_ending'] as $case) {
        $result = qafya_detector()->extract($case['input']);

        expect($result->status())->toBe('partial', "id={$case['id']}")
            ->and($result->rawi())->toBeNull("id={$case['id']}")
            ->and($result->radf())->toBeNull("id={$case['id']}")
            ->and($result->wasl())->toBeNull("id={$case['id']}")
            ->and($result->category())->toBe('unknown', "id={$case['id']}");
    }
});
