<?php

declare(strict_types=1);

namespace Mosab\QafyaDetector\Analysis;

use Mosab\QafyaDetector\Analysis\Rules\CollectiveWawAlefRule;
use Mosab\QafyaDetector\Analysis\Rules\FinalAlefLikeRule;
use Mosab\QafyaDetector\Analysis\Rules\FinalYaaRule;
use Mosab\QafyaDetector\Analysis\Rules\HaaSuffixRule;
use Mosab\QafyaDetector\Analysis\Rules\PoemLevelRule;
use Mosab\QafyaDetector\Analysis\Rules\TaaMarbutaAndDisplayRule;

/**
 * Ordered poem-level qafya laws.
 *
 * The word detector reports local qafya structure. These rules then use the
 * whole poem to decide weak letters, suffixes, orthographic seats, and display
 * repairs before public clustering.
 */
final class PoemLevelRulePipeline
{
    /** @var list<PoemLevelRule> */
    private readonly array $rules;

    /** @param list<PoemLevelRule>|null $rules */
    public function __construct(?array $rules = null)
    {
        $this->rules = $rules ?? [
            new FinalAlefLikeRule,
            new FinalYaaRule,
            new CollectiveWawAlefRule,
            new HaaSuffixRule,
            new TaaMarbutaAndDisplayRule,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $endings
     * @return list<array<string, mixed>>
     */
    public function apply(array $endings): array
    {
        foreach ($this->rules as $rule) {
            $endings = $rule->apply($endings);
        }

        return $endings;
    }
}
