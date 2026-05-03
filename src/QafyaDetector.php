<?php

declare(strict_types=1);

namespace Mosab\QafyaDetector;

use Mosab\QafyaDetector\Contracts\QafyaDetectorContract;
use Mosab\QafyaDetector\Data\PoemQafyaAnalysis;
use Mosab\QafyaDetector\Data\QafyaResult;
use Mosab\QafyaDetector\Support\QafyaOptions;

final class QafyaDetector implements QafyaDetectorContract
{
    public function __construct(
        private readonly WordQafyaDetector $wordDetector = new WordQafyaDetector,
        private readonly PoemQafyaDetector $poemDetector = new PoemQafyaDetector,
    ) {}

    public static function make(): self
    {
        return new self;
    }

    /**
     * @param  array<string, mixed>|QafyaOptions  $options
     */
    public function extract(string $ending, array|QafyaOptions $options = []): QafyaResult
    {
        return $this->wordDetector->detect($ending, $options);
    }

    /**
     * @param  array<string, mixed>|QafyaOptions  $options
     * @return array<string, mixed>
     */
    public function extractArray(string $ending, array|QafyaOptions $options = []): array
    {
        return $this->wordDetector->detectArray($ending, $options);
    }

    /**
     * @param  string|array<int, string>  $poem
     * @param  array<string, mixed>|QafyaOptions  $options
     */
    public function analyze(string|array $poem, array|QafyaOptions $options = []): PoemQafyaAnalysis
    {
        return $this->poemDetector->analyze($poem, $options);
    }

    /**
     * @param  string|array<int, string>  $poem
     * @param  array<string, mixed>|QafyaOptions  $options
     * @return array<string, mixed>
     */
    public function analyzeArray(string|array $poem, array|QafyaOptions $options = []): array
    {
        return $this->poemDetector->analyzeArray($poem, $options);
    }
}
