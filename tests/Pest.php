<?php

declare(strict_types=1);

use Mosab\QafyaDetector\QafyaDetector;

function qafya_detector(): QafyaDetector
{
    return QafyaDetector::make();
}

function fixture_path(string $relative): string
{
    return __DIR__.'/Fixtures/'.ltrim($relative, '/');
}

/**
 * @return array<int, array<string, mixed>>
 */
function load_fixture(string $relative): array
{
    $path = fixture_path($relative);

    expect($path)->toBeFile();

    $data = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

    expect($data)->toBeArray();

    /** @var array<int, array<string, mixed>> $data */
    return $data;
}

/**
 * @return array<int, array{id: string, source: string, lines: list<string>, expected: array<string, string|null>}>
 */
function poetspedia_poem_fixtures(): array
{
    /** @var array<int, array{id: string, source: string, lines: list<string>, expected: array<string, string|null>}> $fixtures */
    $fixtures = load_fixture('poems/poetspedia-50.json');

    return $fixtures;
}

/**
 * @return array<int, array{word: string, expected: array<string, string|null>}>
 */
function edge_case_fixtures(): array
{
    /** @var array<int, array{word: string, expected: array<string, string|null>}> $fixtures */
    $fixtures = load_fixture('edge-cases/edge-cases.json');

    return $fixtures;
}
