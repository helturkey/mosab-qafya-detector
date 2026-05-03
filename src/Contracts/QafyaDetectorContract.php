<?php

declare(strict_types=1);

namespace Mosab\QafyaDetector\Contracts;

use Mosab\QafyaDetector\Data\PoemQafyaAnalysis;
use Mosab\QafyaDetector\Data\QafyaResult;
use Mosab\QafyaDetector\Support\QafyaOptions;

interface QafyaDetectorContract
{
    /**
     * @param  array<string, mixed>|QafyaOptions  $options
     */
    public function extract(string $ending, array|QafyaOptions $options = []): QafyaResult;

    /**
     * @param  array<string, mixed>|QafyaOptions  $options
     * @return array<string, mixed>
     */
    public function extractArray(string $ending, array|QafyaOptions $options = []): array;

    /**
     * @param  string|array<int, string>  $poem
     * @param  array<string, mixed>|QafyaOptions  $options
     */
    public function analyze(string|array $poem, array|QafyaOptions $options = []): PoemQafyaAnalysis;

    /**
     * @param  string|array<int, string>  $poem
     * @param  array<string, mixed>|QafyaOptions  $options
     * @return array<string, mixed>
     */
    public function analyzeArray(string|array $poem, array|QafyaOptions $options = []): array;
}
