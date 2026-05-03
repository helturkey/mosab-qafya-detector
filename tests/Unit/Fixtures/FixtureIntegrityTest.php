<?php

declare(strict_types=1);

it('loads the PoetsPedia corpus fixture and validates its shape', function (): void {
    $fixtures = poetspedia_poem_fixtures();

    expect($fixtures)->toHaveCount(50);

    foreach ($fixtures as $fixture) {
        expect($fixture)->toHaveKeys(['id', 'source', 'lines', 'expected'])
            ->and($fixture['id'])->toBeString()->not->toBe('')
            ->and($fixture['source'])->toBeString()->toStartWith('poetspedia:')
            ->and($fixture['lines'])->toBeArray()->toHaveCount(2)
            ->and($fixture['expected'])->toHaveKey('rawi')
            ->and($fixture['expected']['rawi'])->toBeString()->not->toBe('');
    }
});

it('loads scholarly book-rule fixtures and ensures every listed coverage target exists', function (): void {
    $rules = load_fixture('books/rules.json');

    expect($rules)->not->toBeEmpty();

    $testFiles = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__.'/..'));

    foreach ($iterator as $file) {
        if (! $file instanceof SplFileInfo || ! $file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        $testFiles[] = $file->getFilename();
    }

    foreach ($rules as $rule) {
        expect($rule)->toHaveKeys(['id', 'source', 'claim', 'covered_by'])
            ->and($rule['covered_by'])->toBeArray()->not->toBeEmpty();

        foreach ($rule['covered_by'] as $testFile) {
            expect($testFiles)->toContain($testFile.'.php');
        }
    }
});

it('loads edge-case fixtures and validates supported expectation keys', function (): void {
    $allowed = ['rawi', 'radf', 'wasl', 'khurooj', 'taasis', 'dakhiil'];

    foreach (edge_case_fixtures() as $case) {
        expect($case)->toHaveKeys(['word', 'expected'])
            ->and($case['word'])->toBeString()->not->toBe('')
            ->and($case['expected'])->toBeArray()->not->toBeEmpty();

        foreach (array_keys($case['expected']) as $key) {
            expect($allowed)->toContain($key);
        }
    }
});
