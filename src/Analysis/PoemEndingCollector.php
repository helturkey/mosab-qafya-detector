<?php

declare(strict_types=1);

namespace Mosab\QafyaDetector\Analysis;

use Mosab\QafyaDetector\Support\QafyaOptions;
use Mosab\QafyaDetector\Text\VerseSplitter;
use Mosab\QafyaDetector\WordQafyaDetector;

/**
 * Converts a poem into analyzed sadr/ajz endings.
 *
 * This is the detection orchestration layer. It keeps text splitting and
 * word-level qafya detection outside the poem arbitration class.
 */
final class PoemEndingCollector
{
    public function __construct(
        private readonly WordQafyaDetector $wordDetector = new WordQafyaDetector,
        private readonly VerseSplitter $splitter = new VerseSplitter,
    ) {}

    /**
     * @param  string|array<int, string>  $poem
     * @return array{lines: list<string>, pairs: list<array{index: int, sadr: string, ajz: string}>, endings: list<array<string, mixed>>}
     */
    public function collect(string|array $poem, QafyaOptions $options): array
    {
        $lines = $this->splitter->split($poem);
        $pairs = $this->splitter->pairHemistichs($lines);

        /** @var list<array<string, mixed>> $endings */
        $endings = [];

        foreach ($pairs as $pair) {
            $positions = $options->analyzeSadr
                ? [['position' => 'sadr', 'text' => $pair['sadr']], ['position' => 'ajz', 'text' => $pair['ajz']]]
                : [['position' => 'ajz', 'text' => $pair['ajz']]];

            foreach ($positions as $position) {
                $result = $this->wordDetector->detectArray($position['text'], $options);
                $endings[] = [
                    'bayt' => $pair['index'],
                    'position' => $position['position'],
                    'text' => $position['text'],
                    'result' => $result,
                    'matches_reference' => null,
                    'violations' => [],
                ];
            }
        }

        return [
            'lines' => $lines,
            'pairs' => $pairs,
            'endings' => $endings,
        ];
    }
}
