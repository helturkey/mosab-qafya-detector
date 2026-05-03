<?php

declare(strict_types=1);
use Mosab\QafyaDetector\Analysis\PoemClusterAnalyzer;
use Mosab\QafyaDetector\Analysis\PoemEndingCollector;
use Mosab\QafyaDetector\Analysis\PoemLevelRulePipeline;
use Mosab\QafyaDetector\Analysis\PublicDominantQafyaNormalizer;

it('keeps the poem detector as a thin orchestrator', function (): void {
    $source = file_get_contents(__DIR__.'/../../../src/PoemQafyaDetector.php');

    expect($source)->not->toBeFalse()
        ->and(substr_count((string) $source, "\n"))->toBeLessThan(250)
        ->and(class_exists(PoemEndingCollector::class))->toBeTrue()
        ->and(class_exists(PoemLevelRulePipeline::class))->toBeTrue()
        ->and(class_exists(PoemClusterAnalyzer::class))->toBeTrue()
        ->and(class_exists(PublicDominantQafyaNormalizer::class))->toBeTrue();
});
