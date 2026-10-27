# Source-mapped phonetic representation

`Mosab\QafyaDetector\Text\PhoneticText` is the shared representation between the written ending and internal qafya-boundary logic. Its purpose is to make local rules refer to the correct source letter, including when a letter repeats or shadda expands into two phonetic units.

## Public methods

- `normalizedLetters()` — normalized Arabic letters used by component rules.
- `letterMap()` — normalized-letter entries mapped to original surface letters, Unicode code-point indexes, and UTF-8 byte offsets.
- `units()` — phonetic units with surface letter, vowel, movement/sukun flags, long-vowel type, tanween type, vocalization status, and source offsets.
- `harakaAtLetterIndex(int $letterIndex)` — vowel data for a specific normalized-letter occurrence.
- `diacriticsCoverage()` — a diagnostic summary of visible vocalization coverage.

## Mapping contract

`source_index` and `source_span.start_index` style indexes are Unicode code-point offsets. `source_byte_start` and `source_byte_end` are UTF-8 byte offsets suitable for byte-based slicing; end offsets are exclusive. Shadda expansion creates two phonetic units which intentionally refer to the same surface source span. The displayed surface preserves the written glyph even when the internal phonetic representation maps a final tāʾ marbūṭa to hāʾ at pause or to tāʾ when visibly moving.

## Known modeling boundaries

This is a qafya-boundary representation, not a complete IPA transcriber, morphological analyzer, or connected-speech model.

- Explicit tanween is retained as evidence; the model does not always expand it to an independent /n/ unit because pause/wasl context affects that interpretation.
- Long wāw/yāʾ is classified only when the preceding vowel supports it.
- Missing vowels remain unknown; the code does not label unmarked consonants as definitely moving or sakin.
- Partially vocalized text can produce an incomplete boundary or a documented fallback when the available marks do not identify the boundary securely.
- Fully unvocalized text follows the selected strict/fallback policy and may therefore be less reliable than vocalized text.

Consumers should inspect `segment.complete`, `segment.method`, warnings, and uncertainty diagnostics. Do not assume every returned surface segment was established from complete diacritic evidence.
