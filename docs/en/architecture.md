# Architecture and analysis policy

The implementation separates local word detection from poem-context arbitration. This keeps local observations available even when the poem-level result chooses a different public representation.

## Processing layers

1. **Input normalization** — removes selected invisible characters and normalizes Arabic text for qafya matching while retaining the source form for display and mapping.
2. **Phonetic/source mapping** — `Text\PhoneticText` maps normalized letters and phonetic units to the original Unicode code-point and UTF-8 byte spans.
3. **Word/ending detection** — `WordQafyaDetector`, `Detection\ComponentDetector`, and `Classification\RawiEligibilityPolicy` identify rawi, radf, wasl, khurooj, taasis, dakhiil, motions, signatures, surface pattern, warnings, ambiguities, and rule trace.
4. **Boundary extraction** — `Text\QafyaSegmentExtractor` finds a qafya segment using the selected mode and available diacritics, with explicit completeness and fallback information.
5. **Poem ending collection** — `Analysis\PoemEndingCollector` pairs hemistichs and analyzes the `ajz` by default, or both `sadr` and `ajz` when configured.
6. **Poem-context rules** — `Analysis\PoemLevelRulePipeline` applies selected final alif-like, final yāʾ, collective wāw/alif, hāʾ suffix, and tāʾ marbūṭa rules.
7. **Clustering and arbitration** — `PoemClusterAnalyzer` groups by canonical rawi; `PublicDominantQafyaNormalizer` avoids exposing unstable components as if they were shared by the whole dominant cluster.
8. **Presentation** — immutable response models expose object accessors and JSON-ready arrays.

## Public poem-level fields

The poem response contains `status`, `input`, `reference`, `summary`, `dominant`, `obligations`, `indexing`, `clusters`, `defects`, `sanad`, `endings`, and `diagnostics`. Individual ending records include their input position, word-level result, reference-match information, and violations.

## Why poem-level qafya is grouped by rawi

The public identity of a poem-level qafya is its canonical rawi. A change in radf, wasl, khurooj, taasis, dakhiil, or haraka can matter to the analysis and defect report, but it does not automatically establish a different public qafya if the rawi remains the same.

Examples encoded in the rule tests include:

- `جديدا / يعودا / جيدا / جودا`: alternating wāw/yāʾ before stable rawi `د` does not force one unstable radf into the dominant pattern; the public pattern can remain `دا`.
- `مالئا / هادئا / بادئا`: hamza identity is canonicalized while visible hamza-seat spelling is preserved in the displayed surface.
- Stable hāʾ/alif and yāʾ/hāʾ suffix families are resolved only when poem-wide evidence supports the override.

## Dominant result vs indexing result

`dominant` is the authoritative public qafya payload, and may be `null` when the dominant cluster does not meet the authority threshold. `indexing` exposes the most frequent cluster with ratio and a `usable_for_filtering` flag. `clusters` and `endings` preserve the evidence needed to explain a mixed result. Applications should not replace a missing dominant result with `indexing` and then display it as definitive.

## Vowel motions

The `qafya.motions` object can contain the following classes: `fatha`, `damma`, `kasra`, or `sukun`; values remain `null` where a role is absent or its vowel is not written.

| Key | Traditional term | Interpreted source |
|---|---|---|
| `rass` | rass | Vowel before the taasis alif. |
| `ishbaa` | ishbaa | Vowel of the dakhiil in the relevant structure. |
| `hadhw` | hadhw | Vowel before radf. |
| `tawjih` | tawjih | Vowel before a constrained rawi where the surrounding structure permits it. |
| `mujra` | mujra | Written vowel of the rawi. |
| `nafadh` | nafadh | Vowel of hāʾ wasl before khurooj. |

Poem-level overrides can change which letter occupies a role; in those cases, motions may be reset to `null` rather than carrying over a local guess. A yāʾ-absorption path retains a descriptive `ishbaa` tag as a documented exception. Only `mujra` participates in strict signatures; other motions are informative metadata.

## Defects and reference choice

Poem-level defects are compared against a valid ending in the dominant rawi cluster. The `reference.source` field distinguishes `first_valid_ending` from `first_valid_ending_in_dominant_cluster`. This reduces the chance that one anomalous opening ending becomes the reference for every other bayt.

When the rawi letter is the same and the written vowel changes between two short vowels, the detector distinguishes `iqwa` (kasra versus damma) from `israf` (fatha versus kasra/damma). Sukun-versus-vowel is treated as a possible pause variant rather than automatically reported as either of those defects.

## Final nūn policy

The final nūn logic is intentionally contextual. A visibly vocalized nūn can be treated as nūn al-niswa only when the word resembles an imperfect verb and the surrounding letter conditions match the rule. Other final nūn forms remain eligible for rawi. This remains a heuristic: when morphology is ambiguous, inspect the trace, warnings, and actual ending rather than treating the rule as a complete morphological analyzer.
