# Changelog

## v0.3.0

### Fixed
- **Final nūn:** vowelled broken plurals and plural endings (`أَحْزَانَ`, `أَوْطَانَ`, `أَلْوَانَ`, `أَشْجَانَ`, `تِبْيَانَ`, `يَعْلَمُونَ`) and perfect verbs with a vowelled root letter (`أَحْسَنَ`) were treated as feminine-plural nūn al-niswa, which made the rawi the letter before the nūn and could split an `-ān` poem. The nūn now stays the rawi unless the letter before it is a non-vowelled, non-long-vowel consonant (`يَكْتُبْنَ` still resolves to rawi `ب`, wasl `ن`).
- **Radf:** a waw or yaa that carries an explicit fatha, damma, or kasra before the rawi (`هَوَس`) is a moving consonant and is no longer reported as radf.
- **Motions:** `rass`, `ishbaa`, `hadhw`, `tawjih`, and `nafadh` are now computed from the exact letter positions instead of placeholder strings (`required_before_taasis`, `vowel_before_radf`, `vowel_before_khurooj`) or the wrong letter's vowel. `ishbaa` is the vowel of the dakhiil and `tawjih` is the vowel before a muqayyad rawi. Poem-level overrides no longer write placeholder strings.
- **Defect reference:** poem-level defects are measured against the first valid ending of the dominant rawi cluster instead of always the first ending.
- **Defect names:** fatha against kasra or damma on the rawi is now `israf`; kasra against damma stays `iqwa`.

### Behavior changes (review before upgrading)
- `reference.source` may be `first_valid_ending_in_dominant_cluster` in addition to `first_valid_ending`.
- A sukun against a vowel on the rawi is no longer reported as `iqwa`; it is treated as a pausal variant.
- Motion fields are `null` or one of `fatha`, `damma`, `kasra`, `sukun`.
- The status note in the README no longer says "production-ready".
- Poem-level `israf` defects are possible where fatha/damma changes were previously labeled `iqwa`.

### Foundation work in this release line
- Source-mapped phonetic layer (`PhoneticText`) shared by the component detector and the qafya segment extractor; exact-position haraka lookup; shadda expansion; long-vowel evidence; unknown vowels are not counted as moving letters; the moving count and qafya name are unknown (`null`) when internal consonants are unmarked.
- Mode selection follows the ending's diacritics; unpaired trailing hemistichs give `partial` status.
- Evidence, uncertainty, and a minimal candidate list in word diagnostics; uncalibrated confidence labels; calibration-profile gating.
- Rule registry and trace audit, reference-corpus schema/validator/evaluator, and an optional diagnostic-only `LinguisticEvidenceProvider` contract.

### Tests and fixtures
- Word fixtures: 66 words covering all 21 component-detector rules, with expected components, category, vowel motions, trace rule, and ambiguity types; a test fails if a rule has no fixture. Input-normalization fixtures cover punctuation, invisible characters, Latin text, digits, tatweel, and inputs with no Arabic ending.
- Poem fixtures: 12 multi-bayt regression poems covering clusters, minority first/last endings, reference choice, `sanad_radf`, `sanad_taasis`, `iqwa`, `israf`, pausal sukun, `ita` and its seven-bayt boundary, and the 0.8 authority boundary.
- Direct unit tests for `RhymeDefectDetector`, `SanadDetector`, `PoemClusterAnalyzer`, `QafyaObligationResolver`, `QafyaSignatureBuilder`, `QafyaSubtypeClassifier`, `RawiEligibilityPolicy`, `PoemAnalysisPresenter`, `PoemEndingCollector`, `VerseSplitter`, `QafyaOptions`, `QafyaSurfaceFormatter`, `ArabicText`, `ArabicLetters`, `HarakaLocator`, `PhoneticText` coverage, data objects, enums, the linguistic-evidence provider, and the contracts.
- CLI tests for `bin/qafya` (commands, options, exit codes, JSON output).
- `tests/Fixtures/README.md` and `docs/testing.md` describe the layers and how to add fixtures. All fixture records are `unreviewed`.

### Known limitations
- Pinned by `tests/Fixtures/words/known-limitations.json`: `final_weak_after_alef_as_rawi` fires when any madd letter, not only alif, precedes a final waw/yaa, so `نَبَوِيّ`, `قَوِيّ`, and `حَيِيّ` report a spurious radf `ا`, and `ثَاوِي` reports rawi `ي` instead of `و` with wasl `ي`.
- An ending with no detectable rawi becomes its own "unknown" cluster, which can make a poem look like it has multiple qafyas (pinned in `PoemEdgeCasesTest`).
- `QafyaDefect` has no cases for `sanad_radf`, `sanad_taasis`, `wasl_mismatch`, or `khurooj_mismatch`, which the defect detector emits as plain strings.
- The Laravel service provider and facade have no direct tests.
- The reference corpus contains only a few unreviewed regression records, so no accuracy figure can be claimed. `tests/Fixtures/poems/poetspedia-50.json` is single-bayt and checks the rawi only.
- The noon rule, haa rawi/wasl decisions, and alif/alif-maqsura promotion are heuristics.
- Undiacritized endings produce estimates and warnings, not verified results.
- The yaa-absorption poem rule still emits a descriptive `ishbaa` tag.
- The v0.3.0 edits listed under "Fixed" were written without executing PHP: run `composer test`, `composer analyse`, and `composer lint` before tagging.
