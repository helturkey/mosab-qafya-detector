<?php

declare(strict_types=1);

namespace Mosab\QafyaDetector\Analysis\Rules;

interface PoemLevelRule
{
    /**
     * @param  list<array<string, mixed>>  $endings
     * @return list<array<string, mixed>>
     */
    public function apply(array $endings): array;
}
