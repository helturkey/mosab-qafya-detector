<?php

declare(strict_types=1);

namespace Mosab\QafyaDetector\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use Mosab\QafyaDetector\Data\PoemQafyaAnalysis;
use Mosab\QafyaDetector\Data\QafyaResult;

/**
 * @method static QafyaResult extract(string $ending, array<string, mixed> $options = [])
 * @method static array<string, mixed> extractArray(string $ending, array<string, mixed> $options = [])
 * @method static PoemQafyaAnalysis analyze(string|array<int, string> $poem, array<string, mixed> $options = [])
 * @method static array<string, mixed> analyzeArray(string|array<int, string> $poem, array<string, mixed> $options = [])
 */
final class QafyaDetector extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Mosab\QafyaDetector\QafyaDetector::class;
    }
}
