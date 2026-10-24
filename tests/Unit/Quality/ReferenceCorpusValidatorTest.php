<?php

declare(strict_types=1);

use Mosab\QafyaDetector\Support\ReferenceCorpusValidator;

it('accepts the seed reference corpus while excluding unreviewed examples from scoring', function (): void {
    $path = dirname(__DIR__, 3).'/fixtures/reference-corpus/reference-corpus-v1.json';
    $corpus = json_decode((string) file_get_contents($path), true);
    $validator = new ReferenceCorpusValidator;

    expect(is_array($corpus))->toBeTrue()
        ->and($validator->validate($corpus))->toBe([])
        ->and($validator->verifiedCount($corpus))->toBe(0);
});

it('rejects a verified record without reviewer provenance and full expected fields', function (): void {
    $corpus = [
        'schema_version' => 1,
        'metadata' => [],
        'records' => [[
            'id' => 'bad-verified',
            'kind' => 'ending',
            'input' => 'كلمة',
            'split' => 'test',
            'source' => ['type' => 'test', 'reference' => 'fixture', 'locator' => 'case-1'],
            'expected' => ['rawi' => 'ة'],
            'review' => ['status' => 'verified'],
        ]],
    ];

    $errors = (new ReferenceCorpusValidator)->validate($corpus);
    expect($errors)->not->toBe([])
        ->and(implode("\n", $errors))->toContain('review.reviewer')
        ->and(implode("\n", $errors))->toContain('expected.pattern_surface');
});
