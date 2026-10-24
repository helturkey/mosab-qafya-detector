# Shared phonetic representation

`Mosab\QafyaDetector\Text\PhoneticText` is the common boundary between the written ending and its internal phonetic units. It exposes:

- `normalizedLetters()` for the normalized character sequence used by local component rules;
- `letterMap()` for normalized-character to original code-point and UTF-8 byte spans;
- `units()` for prosodic units, including shadda expansion, long-vowel classification where the local mark supports it, explicit tanween annotations, and explicit/inferred/unknown vowel status;
- `harakaAtLetterIndex()` for mark lookup by occurrence, avoiding “last matching glyph” mistakes;
- `diacriticsCoverage()` for a disclosed coverage diagnostic.

Both `ComponentDetector` and `QafyaSegmentExtractor` use this source-mapped representation directly. The stock `ArudicNormalizer` is a compatibility facade over `PhoneticText`; during patch installation, a locally modified `ArudicNormalizer.php` is preserved rather than overwritten. Shadda creates two units pointing to the same source glyph. The visible surface keeps the original written letter, while the arudic string may contain the phonetic representation (`ة` at pause can be represented as `ه`; a visibly moving final `ة` can be represented as `ت`). Source spans use end-exclusive offsets. `source_index` and `source_span.start_index` are Unicode code-point offsets; `byte_start`/`byte_end` are UTF-8 byte offsets suitable for string slicing.

This is a source-mapped prosodic representation for qafya boundary analysis, not a complete IPA transcriber or a connected-speech model. Tanween is preserved as explicit evidence; it is not automatically expanded to a separate /n/ unit, because that requires a contextual pause/wasl policy. Long wāw/yāʾ is recognized only when its preceding short vowel supports that reading. The representation does not infer a missing vowel as known. If an ending contains some but not enough diacritics to identify two sakins, the extractor returns an incomplete boundary fallback. If two sakins remain identifiable, it may return that boundary while leaving the moving-letter count unknown and attaching a partial-diacritization warning. Completely unvocalized endings still follow the configured strict/fallback policy. These choices must be evaluated against a larger human-reviewed reference corpus before changing their defaults.
