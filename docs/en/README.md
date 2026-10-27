# Mosab Qafya Detector — User Guide

This guide describes the public API, runtime options, poem-level policy, Laravel integration, and operational behavior of `helturkey/mosab-qafya-detector`.

## Live use: Poetspedia

This package was built for **classifying Arabic poems by qafya at poem level**, not only for inspecting isolated words. Its poem-classification capability is already running on **[Poetspedia](https://poetspedia.com/)**. You can see the public results on the live **[Arabic rhyme-classification page](https://poetspedia.com/rhymes/)**.

This is an operating implementation, not a planned feature. The public page nevertheless labels automatic rhyme classification as experimental: rare, weakly vocalized, or linguistically ambiguous endings can require human review. The package surfaces states, clusters, evidence, and diagnostics so consuming applications can avoid presenting every result as an unquestionable scholarly verdict. Details: [Live classification and scope](production-use.md).

## 1. Installation and requirements

```bash
composer require helturkey/mosab-qafya-detector
```

Requirements: PHP `^8.4`, Composer, and the `mbstring` extension. The core detector does not require a third-party NLP service. Laravel integration is automatically discovered when installed in a Laravel application.

## 2. Analyze one word or ending

```php
use Mosab\QafyaDetector\QafyaDetector;

$detector = QafyaDetector::make();
$result = $detector->extract('ناداها');

$result->rawi();            // ه
$result->radf();            // ا
$result->wasl();            // ا
$result->patternSurface();  // اها
$result->segmentSurface();
$result->confidenceLevel();
$result->warnings();
$result->diagnostics;
$result->toArray();
```

The detector returns a `QafyaResult` value object. Use the convenience accessors for common fields and `toArray()` when storing, serializing, or returning the result from an API.

## 3. Analyze a poem

Provide a string or a list of hemistichs. The splitter groups successive lines into bayts. The default setting analyzes the `ajz` (second hemistich) of each complete bayt; `analyze_sadr: true` analyzes both `sadr` and `ajz`.

```php
$analysis = $detector->analyze([
    'قفْ بالديار وصحْ إلى بيداها',
    'فعسى الديار تجيبُ منْ ناداها',
    'دارٌ يفوحُ المِسْك من عَرَصاتِها',
    'والعودُ والندُّ الذكيُّ جناها',
]);

$analysis->status();
$analysis->rawi();
$analysis->qafyaPattern();
$analysis->hasMultipleQafyas();
$analysis->dominantRatio();
$analysis->dominant();
$analysis->indexing();
$analysis->clusters();
$analysis->endings();
$analysis->defects();
$analysis->sanad();
$analysis->toArray();
```

For reliable poem-level decisions, provide the poem in its intended bayt order and do not combine separate qafya sections into one input. An odd number of input lines is reported as partial/unpaired input rather than silently treated as a complete bayt.

## 4. Options

Pass an array or an immutable `QafyaOptions` instance.

| Option | Default | Behavior |
|---|---:|---|
| `mode` | `auto` | One of `auto`, `heuristic`, `scholarly`; `auto` selects according to meaningful diacritics on the final Arabic word. |
| `strict_harakat` | `true` | Controls strictness of boundary extraction when vowel evidence is incomplete. |
| `analyze_sadr` | `false` | Analyze both hemistich positions rather than `ajz` only. |
| `detect_defects` | `true` | Enable poem-level defect detection. |
| `detect_sanad` | `true` | Enable sanad findings derived from detected defects. |
| `use_linguistic_evidence` | `false` | Include supplemental evidence from an explicitly injected provider. |
| `confidence_calibration_profile` | `null` | Optional calibration profile; only profiles that satisfy runtime validation gates are applied. |

Example:

```php
use Mosab\QafyaDetector\Support\QafyaOptions;

$options = new QafyaOptions(
    mode: 'auto',
    strictHarakat: true,
    analyzeSadr: false,
    detectDefects: true,
    detectSanad: true,
);

$result = $detector->extract('كتابُهُ', $options);
```

The mode name `scholarly` is an analysis setting. It does not mean that a scholar reviewed or certified a result.

## 5. Result statuses

| Status | Meaning | Recommended application behavior |
|---|---|---|
| `ok` | A rawi was identified for the analyzed ending, or poem analysis completed without a review-triggering condition. | Display the result with its evidence and diagnostics. |
| `review` | Poem analysis found a condition that needs review, such as multiple rawi clusters or a blocking defect. | Do not present one qafya as authoritative without checking the details. |
| `partial` | Input pairing, qafya boundary, or evidence was incomplete. | Preserve the response and explain what was not resolved. |
| `error` | The input could not be analyzed. | Surface the error field and validate the input. |

A successful method call is not the same as linguistic certainty. Check the status, warnings, ambiguities, completeness flags, and candidates before using the result for scholarly or editorial claims.

## 6. Poem-level policy: rawi identity

Public poem-level clusters are grouped by canonical rawi identity. Variations in `radf`, `wasl`, `khurooj`, `taasis`, `dakhiil`, or haraka remain visible in ending details and may be reported as defects or sanad; by themselves, these variations do not create a second public qafya when the rawi is unchanged.

Examples of contextual rules include:

- Final `ا/ى` can act as wasl after a stable preceding consonant, or be considered in rawi arbitration when the preceding letters vary across the poem.
- Haa may be rawi, wasl, or part of a suffix. Context is used where the rules can establish it.
- A radf is included in the public dominant pattern only when the exact radf is stable in the cluster.
- Poem-level overrides handle selected final yāʾ, collective `وا`, pronoun-suffix, and tāʾ marbūṭa cases.

These are explicit heuristics encoded in the rule pipeline; ambiguous cases should remain reviewable rather than being treated as universally resolved.

## 7. `dominant`, `indexing`, clusters, and endings

- `dominant`: the public qafya payload when the dominant rawi cluster is authoritative; it may be `null` for a mixed or weakly dominant poem.
- `indexing`: most-frequent cluster metadata intended for filtering and browse/search use. `usable_for_filtering` records the package's authority decision.
- `clusters`: rawi-level cluster counts and ratios.
- `endings`: individual analyzed endings, including the word-level result, chosen reference match, and violations.

Use `hasMultipleQafyas()` as a decision signal, not as an error indicator. For a mixed poem, show clusters/endings rather than forcing a single rawi.

## 8. Word result and diagnostics

`QafyaResult` exposes common accessors such as `rawi()`, `radf()`, `wasl()`, `khurooj()`, `taasis()`, `dakhiil()`, `segmentSurface()`, `patternSurface()`, `confidenceScore()`, `confidenceLevel()`, `warnings()`, `defects()`, `components()`, `motions()`, and `toArray()`.

The serialized response separates `qafya`, `segment`, `pattern`, `signatures`, `confidence`, `diagnostics`, and `trace`. `diagnostics` may contain warnings, ambiguities, candidates, evidence, uncertainty, optional linguistic evidence, and defects. Unknown rule IDs should be visible during audits; they must not be treated as silently verified.

## 9. Laravel integration

```php
use Mosab\QafyaDetector\Laravel\Facades\QafyaDetector;

$result = QafyaDetector::extract('كتابُهُ');
$analysis = QafyaDetector::analyze($poem);
```

Publish the package configuration when you want a project-owned copy:

```bash
php artisan vendor:publish --tag=mosab-qafya-detector-config
```

Configuration keys are `default_mode`, `analyze_sadr`, `strict_harakat`, `detect_defects`, and `detect_sanad`. Per-call options are available in the public API. Do not assume publishing the config changes every direct `QafyaDetector::make()` call unless your application wiring explicitly uses those config values.

## 10. CLI

From a source checkout:

```bash
php bin/qafya --help
php bin/qafya word "البركة"
php bin/qafya word "البركة" "الديكة" --json
php bin/qafya poem poem.txt --json
```

From an application that installed the dependency:

```bash
php vendor/helturkey/mosab-qafya-detector/bin/qafya word "البركة" --json
```

A poem file contains one hemistich per line; `***` may separate the two halves of a bayt. CLI options include `--json`, `--mode=auto|heuristic|scholarly`, `--no-strict-harakat`, `--analyze-sadr`, `--no-defects`, and `--no-sanad`.

## 11. Enums and stable integration

Enums such as `QafyaStatus`, `QafyaCategory`, `QafyaMotion`, and `QafyaDefect` expose `label()` and `description()` for UI strings. Prefer comparing machine values (for example, `rawi_mismatch`) rather than localized labels. When persisting results, treat the response as versioned application data and handle additive fields in serialized payloads.

## 12. Further documentation

- [Architecture](architecture.md)
- [Testing and fixtures](testing.md)
- [Rule registry and trace audit](rule-registry.md)
- [Source-mapped phonetic representation](phonetic-representation.md)
- [Optional linguistic providers](linguistic-providers.md)
- [Live classification and scope](production-use.md)
- [Release checklist](release-checklist.md)
