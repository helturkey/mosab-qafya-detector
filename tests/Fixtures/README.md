# Test fixtures

All fixtures here are **regression fixtures, not a verified corpus.** Every record is marked `review_status: "unreviewed"`: no prosody scholar has checked them, and they must not be quoted as accuracy figures. Verified records belong in `fixtures/reference-corpus/` (see `docs/reference-corpus.md`).

| File | Purpose | Consumed by |
|---|---|---|
| `words/word-components.json` | One word per detector rule (all 21 rules) plus variants. `expected` equals current behavior; `classical_checked` lists the keys the author also compared with the classical reading. `group: heuristic` marks word-level defaults for ambiguous cases. | `tests/Unit/Fixtures/WordComponentsFixtureTest.php` |
| `words/known-limitations.json` | Words whose current result differs from the classical reading. Tests pin the current result so a fix is a deliberate change. | same |
| `words/input-normalization.json` | Noisy input (punctuation, invisible characters, Latin text, digits, tatweel) that must reduce to the same ending, and inputs with no Arabic ending. | same |
| `poems/multi-bayt-regression.json` | Multi-bayt poems built only from consonant-rawi endings, so no poem-level override rule applies; they pin clusters, defects, reference choice, and thresholds. | `tests/Unit/Fixtures/MultiBaytPoemFixtureTest.php` |
| `poems/poetspedia-50.json` | 50 single-bayt poems; checks the rawi only. | `tests/Unit/Fixtures/PoetsPediaCorpusTest.php` |
| `edge-cases/edge-cases.json` | Short word-level edge cases. | `tests/Unit/Fixtures/FixtureIntegrityTest.php` |

## Adding a word

1. Add an entry to `words/word-components.json` with a unique `id`, the `word`, a `group`, the trace `rule`, the `category`, the six `expected` components, the six `motions`, any `ambiguous` types, and `review_status: "unreviewed"`.
2. If the current result is wrong by the classical reading, put the word in `words/known-limitations.json` instead, with both the `classical` and `current` values.
3. A new detector rule needs a fixture word and an entry in `all_component_rule_ids()` in `WordComponentsFixtureTest.php`; a test fails if a listed rule has no fixture.

## Adding a poem

Use consonant-rawi endings (not `ا ى و ي ه ة ء` as the final letter) unless you are deliberately testing a poem-level override rule. Fill in every `expected` key, including `defects` as `{type: [bayt indexes]}`.
