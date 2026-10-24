<?php

declare(strict_types=1);

namespace Mosab\QafyaDetector\Text;

/**
 * Compatibility facade over PhoneticText. Word-level detection, haraka lookup,
 * and qafya-boundary extraction now share one source-mapped representation.
 */
final class ArudicNormalizer
{
    /**
     * @return list<array{letter:string,surface_letter:string,haraka:?string,is_sakin:bool,is_moving:bool,is_long_vowel:bool,long_vowel_kind:?string,tanween_type:?string,vocalization_status:string,original_index:int,source_index:int,source_byte_start:int,source_byte_end:int}>
     */
    public function units(string $text): array
    {
        return (new PhoneticText($text))->units();
    }

    /** @return array{letter_count:int,marked_count:int,unmarked_internal_consonants:int,coverage:float,fully_marked_for_boundary:bool} */
    public function diacriticsCoverage(string $text): array
    {
        return (new PhoneticText($text))->diacriticsCoverage();
    }
}
