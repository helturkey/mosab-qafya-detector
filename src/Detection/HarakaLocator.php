<?php

declare(strict_types=1);

namespace Mosab\QafyaDetector\Detection;

use Mosab\QafyaDetector\Support\ArabicLetters;
use Mosab\QafyaDetector\Support\ArabicText;

/**
 * Finds harakat attached to Arabic letters in the original ending.
 */
final class HarakaLocator
{
    /**
     * @return array{mark: ?string, name: ?string, class: ?string}
     */
    public function lastForLetter(string $text, ?string $letter): array
    {
        if ($letter === null || $letter === '') {
            return $this->empty();
        }

        $chars = ArabicText::chars($text);
        for ($i = count($chars) - 1; $i >= 0; $i--) {
            $current = ArabicText::normalizeForRawi($chars[$i]);
            if ($current === $letter) {
                return $this->attachedAt($chars, $i);
            }
        }

        return $this->empty();
    }

    /**
     * @param  list<string>  $chars
     * @return array{mark: ?string, name: ?string, class: ?string}
     */
    private function attachedAt(array $chars, int $index): array
    {
        /** @var list<string> $marks */
        $marks = [];
        for ($i = $index + 1; $i < count($chars); $i++) {
            if (! ArabicLetters::isHaraka($chars[$i])) {
                break;
            }
            $marks[] = $chars[$i];
        }

        foreach (array_reverse($marks) as $mark) {
            if ($mark !== ArabicLetters::SHADDA) {
                return [
                    'mark' => $mark,
                    'name' => ArabicLetters::harakaName($mark),
                    'class' => ArabicLetters::harakaClass($mark),
                ];
            }
        }

        return $this->empty();
    }

    /**
     * @return array{mark: ?string, name: ?string, class: ?string}
     */
    private function empty(): array
    {
        return ['mark' => null, 'name' => null, 'class' => null];
    }
}
