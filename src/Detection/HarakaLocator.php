<?php

declare(strict_types=1);

namespace Mosab\QafyaDetector\Detection;

use Mosab\QafyaDetector\Support\ArabicText;
use Mosab\QafyaDetector\Text\PhoneticText;

/** Finds harakat through the shared source-mapped phonetic representation. */
final class HarakaLocator
{
    /** @return array{mark: ?string, name: ?string, class: ?string} */
    public function lastForLetter(string $text, ?string $letter): array
    {
        if ($letter === null || $letter === '') {
            return $this->empty();
        }

        $phoneticText = new PhoneticText($text);
        $chars = $phoneticText->normalizedLetters();
        $target = ArabicText::normalizeForRawi($letter);

        for ($i = count($chars) - 1; $i >= 0; $i--) {
            if (ArabicText::normalizeForRawi($chars[$i]) === $target) {
                return $phoneticText->harakaAtLetterIndex($i);
            }
        }

        return $this->empty();
    }

    /**
     * Look up the diacritics attached to a specific Arabic-letter ordinal.
     * The ordinal uses the normalized, tashkeel-free form consumed by the
     * detector, so repeated letters cannot silently select the wrong haraka.
     *
     * @return array{mark: ?string, name: ?string, class: ?string}
     */
    public function atLetterIndex(string $text, int $letterIndex): array
    {
        return (new PhoneticText($text))->harakaAtLetterIndex($letterIndex);
    }

    /** @return array{mark: ?string, name: ?string, class: ?string} */
    private function empty(): array
    {
        return ['mark' => null, 'name' => null, 'class' => null];
    }
}
