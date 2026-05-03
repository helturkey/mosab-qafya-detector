<?php

declare(strict_types=1);

namespace Mosab\QafyaDetector\Analysis\Rules;

use Mosab\QafyaDetector\Classification\QafyaSignatureBuilder;

abstract class AbstractPoemLevelRule implements PoemLevelRule
{
    public function __construct(
        protected readonly QafyaSignatureBuilder $signatureBuilder = new QafyaSignatureBuilder,
    ) {}

    /** @param array<string, mixed> $ending */
    protected function normalizedEndingWord(array $ending): ?string
    {
        $value = $ending['result']['input']['normalized'] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    /** @param array<string, mixed> $ending */
    protected function endingWord(array $ending): ?string
    {
        $value = $ending['result']['input']['normalized'] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    /** @param array<string, mixed> $result */
    protected function componentLetter(array $result, string $key): ?string
    {
        $value = $result['qafya']['components'][$key]['letter'] ?? null;

        return is_string($value) ? $value : null;
    }

    /**
     * @param  array<string, mixed>  $result
     * @return list<string>
     */
    protected function traceRules(array $result): array
    {
        $rules = [];

        foreach (($result['trace'] ?? []) as $trace) {
            if (is_array($trace) && is_string($trace['rule'] ?? null)) {
                $rules[] = $trace['rule'];
            }
        }

        return $rules;
    }
}
