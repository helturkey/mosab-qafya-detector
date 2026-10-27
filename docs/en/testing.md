# Testing, fixtures, and quality gates

## Standard checks

Run from the repository root with dependencies installed:

```bash
composer validate --strict
composer test
composer analyse
composer lint
```

Optional utility checks:

```bash
php benchmarks/basic.php
```

`composer test` runs Pest, `composer analyse` runs PHPStan over `src/`, and `composer lint` runs Pint in test mode. Run these checks against the exact commit and PHP version you intend to ship; a successful syntax check or a focused test is not a substitute for the complete suite.

## Test layers

| Layer | Location | Scope |
|---|---|---|
| Word fixtures | `tests/Fixtures/words/` and `tests/Unit/Fixtures/WordComponentsFixtureTest.php` | Components, motions, categories, trace rule IDs, ambiguity flags, and input normalization. |
| Multi-bayt fixtures | `tests/Fixtures/poems/multi-bayt-regression.json` | Clusters, authority thresholds, reference selection, defect types, and bayt indexes. |
| Poetspedia fixtures | `tests/Fixtures/poems/poetspedia-50.json` | A 50-entry regression set for rawi detection. |
| Unit tests | `tests/Unit/` | Component detection, clustering, defect classification, options, text mapping, response models, enums, and poem-level rules. |
| CLI tests | `tests/Feature/CliTest.php` | CLI syntax, options, exit codes, and JSON output. |
| Architecture tests | `tests/Unit/Architecture/` | Selected dependency and size constraints. |

## Regression fixture policy

Regression fixtures capture current expected behavior. They are useful for preventing accidental changes but do not, by themselves, establish scholarly correctness. The `known-limitations.json` file preserves known disagreements so a correction can be made deliberately and accompanied by a new test.

Every new rule should include:

1. A unique rule ID in the trace and a record in `QafyaRuleRegistry`.
2. At least one focused word-level or poem-level regression test.
3. A documented condition and exception.
4. A source citation reviewed to the extent available; passing a test is not scholarly certification.
