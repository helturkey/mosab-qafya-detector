# Mosab Qafya Detector

**Mosab Qafya Detector** is a PHP library for structured analysis of classical Arabic rhyme (*qāfiyah*). It extracts the rawī and related components, analyzes poem endings in context, reports possible rhyme defects and *sanād*, and returns typed PHP results or JSON-ready arrays.

The package is designed for integration into PHP applications and Laravel projects. Results include status, evidence, warnings, ambiguity signals, rule traces, clustering, and review-oriented diagnostics so an application can distinguish a usable result from one that needs editorial review.

## Requirements

- PHP **8.4 or later** (`^8.4`).
- The PHP `mbstring` extension for Unicode-aware operations.
- Composer.
- Laravel integration is optional; the service provider targets Laravel applications using supported Illuminate versions.

## Installation

```bash
composer require helturkey/mosab-qafya-detector
```

## Quick start: analyze an ending

```php
use Mosab\QafyaDetector\QafyaDetector;

$detector = QafyaDetector::make();
$result = $detector->extract('ناداها');

$result->status();          // ok
$result->rawi();            // ه
$result->radf();            // ا
$result->wasl();            // ا
$result->patternSurface();  // اها
$result->confidenceLevel();
$result->warnings();
```

## Analyze a poem

Pass the poem as a list of hemistichs or as a string that the line splitter can parse. By default, only the second hemistich (*ʿajuz*) of each complete bayt is used for poem-level clustering. Set `analyze_sadr` to `true` to analyze both halves.

```php
$analysis = $detector->analyze([
    'قفْ بالديار وصحْ إلى بيداها',
    'فعسى الديار تجيبُ منْ ناداها',
]);

$analysis->status();
$analysis->rawi();
$analysis->qafyaPattern();
$analysis->hasMultipleQafyas();
$analysis->dominantRatio();
$analysis->clusters();
$analysis->endings();
$analysis->defects();
$analysis->sanad();
```

## Runtime options

Options can be passed as an associative array or as `Mosab\QafyaDetector\Support\QafyaOptions`:

```php
$analysis = $detector->analyze($poem, [
    'mode' => 'auto',                 // auto | heuristic | scholarly
    'strict_harakat' => true,
    'analyze_sadr' => false,
    'detect_defects' => true,
    'detect_sanad' => true,
    'use_linguistic_evidence' => false,
]);
```

`auto` selects the boundary-analysis mode from meaningful diacritics on the final Arabic word. The `scholarly` mode is a processing mode name, not a claim that an output has been certified by a human scholar. Linguistic providers and confidence-calibration profiles are opt-in; see the full guide.

## Laravel

The package registers its service provider through Laravel package discovery.

```php
use Mosab\QafyaDetector\Laravel\Facades\QafyaDetector;

$result = QafyaDetector::extract('كتابُهُ');
$analysis = QafyaDetector::analyze($poem);
```

Publish the optional configuration file:

```bash
php artisan vendor:publish --tag=mosab-qafya-detector-config
```

## Command-line tool

From the source checkout:

```bash
php bin/qafya word "البركة"
php bin/qafya word "البركة" "الديكة" --json
php bin/qafya poem poem.txt --json
```

For an installed dependency, run `php vendor/helturkey/mosab-qafya-detector/bin/qafya ...` from the consuming application's root. The poem file uses one hemistich per line; the CLI also documents `***` as a separator between the two halves of a bayt. Use `--help` for all options.

## Interpreting results

- `ok`: the detector produced a rawi result for the analyzed input.
- `review`: poem-level diagnostics indicate that the result should be reviewed, such as multiple rawi clusters or blocking defects.
- `partial`: the input or available phonetic evidence was insufficient for a complete analysis.
- `error`: analysis could not be completed for the input.

At poem level, canonical **rawi identity** determines the public qafya cluster. Other components (radf, wasl, khurooj, taasis, dakhiil, and vowel motions) remain available in per-ending details and can contribute defects or sanad. `dominant` is the authoritative public result when the dominant cluster meets the package's authority policy; `indexing` exposes the most frequent cluster for browsing and filtering.

Rule-based confidence scores are evidence scores unless a separately supplied calibration profile has passed the implementation's validation gates. Do not interpret a raw score as a statistically calibrated probability. Ambiguous and weakly vocalized poetry should be reviewed by a qualified reader.

## Quality commands

Run these from the repository root:

```bash
composer validate --strict
composer test
composer analyse
composer lint
```

The commands check package metadata, regressions, static analysis and style. A syntactically valid regression fixture is not the same as a human-verified scholarly benchmark. Do not publish accuracy figures unless verified records and evaluation results support them.

## Documentation

- [English guide](docs/en/README.md)
- [الدليل العربي](docs/ar/README.md)
- [Architecture](docs/en/architecture.md) · [Testing](docs/en/testing.md) · [Rule registry](docs/en/rule-registry.md)
- [بنية الحزمة](docs/ar/architecture.md) · [الاختبارات](docs/ar/testing.md) · [سجل القواعد](docs/ar/rule-registry.md)

## Project origin

Mosab Qafya Detector grew out of Arabic qafya-analysis work for **[موسوعة الشعراء](https://poetspedia.com/)**. The package was separated as a reusable PHP library, while its poem-classification capability is already used by the live [الرويّ والقافية](https://poetspedia.com/rhymes/). It is intended for Arabic-poetry developers, researchers, and language-tool authors who need structured results that can be integrated into applications.

## Live use: Arabic poem classification on Poetspedia

This package was built to classify Arabic poems by **qafya (rhyme) at poem level**, including the rawi-based grouping used for browsing and classification. This functionality is **already working on the live Poetspedia site**; it is not merely a proposed feature or a future roadmap item.

- **Poetspedia:** <https://poetspedia.com/>
- **Live Arabic rhyme-classification page:** <https://poetspedia.com/rhymes/>

The public rhyme page currently describes automated classification as experimental and notes that difficult cases may be inaccurate. That is compatible with the package's operating role: it performs real classification, exposes clusters and diagnostics, and preserves uncertainty for review rather than claiming scholarly certainty for every unvocalized or ambiguous poem. See [the production-use notes](docs/en/production-use.md) for the scope and limitations.
