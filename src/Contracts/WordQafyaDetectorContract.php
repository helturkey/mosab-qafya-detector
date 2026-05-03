<?php

declare(strict_types=1);

namespace Mosab\QafyaDetector\Contracts;

use Mosab\QafyaDetector\Data\QafyaResult;
use Mosab\QafyaDetector\Support\QafyaOptions;

interface WordQafyaDetectorContract
{
    /**
     * @param  array<string, mixed>|QafyaOptions  $options
     */
    public function detect(string $ending, array|QafyaOptions $options = []): QafyaResult;

    /**
     * @param  array<string, mixed>|QafyaOptions  $options
     * @return array<string, mixed>
     */
    public function detectArray(string $ending, array|QafyaOptions $options = []): array;
}
