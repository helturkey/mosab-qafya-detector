<?php

declare(strict_types=1);

namespace Mosab\QafyaDetector\Contracts;

use Mosab\QafyaDetector\Data\PoemQafyaAnalysis;
use Mosab\QafyaDetector\Support\QafyaOptions;

interface PoemQafyaDetectorContract
{
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
