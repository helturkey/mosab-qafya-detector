# Mosab Qafya Detector

**Mosab Qafya Detector** is a PHP-first package for Arabic qafya detection. It extracts the core rhyme letter (**rawi**), qafya components such as **radf**, **wasl**, **khurooj**, **taasis**, and **dakhiil**, and provides poem-level analysis with diagnostics, clusters, defects, sanad, and review flags.

> This is a production-ready machine-assisted qafya detector. It is not a replacement for human scholarly verification in ambiguous poems.

---

## Installation

```bash
composer require helturkey/mosab-qafya-detector
```

---

## Quick Usage

```php
use Mosab\QafyaDetector\QafyaDetector;

$result = QafyaDetector::make()->extract('ناداها');

$result->rawi();           // ه
$result->radf();           // ا
$result->wasl();           // ا
$result->patternSurface(); // اها
```

---

## Analyze a Poem

```php
$analysis = QafyaDetector::make()->analyze([
    'قفْ بالديار وصحْ إلى بيداها',
    'فعسى الديار تجيبُ منْ ناداها',
]);

$analysis->status();             // ok|review|partial|error
$analysis->rawi();               // ه
$analysis->qafyaPattern();       // اها
$analysis->hasMultipleQafyas();  // false
$analysis->clusters();
$analysis->endings();
```

---

## Laravel

```php
use Mosab\QafyaDetector\Laravel\Facades\QafyaDetector;

$result = QafyaDetector::extract('كتابُهُ');
$analysis = QafyaDetector::analyze($poem);
```

Publish config:

```bash
php artisan vendor:publish --tag=mosab-qafya-detector-config
```

---

## API

```php
QafyaDetector::make()->extract(string $text, array|QafyaOptions $options = []): QafyaResult
QafyaDetector::make()->extractArray(string $text, array|QafyaOptions $options = []): array

QafyaDetector::make()->analyze(string|array $poem, array|QafyaOptions $options = []): PoemQafyaAnalysis
QafyaDetector::make()->analyzeArray(string|array $poem, array|QafyaOptions $options = []): array
```

---

## Poem-Level Policy

The public poem-level qafya identity is based on **rawi**.

- `radf`, `wasl`, `khurooj`, `taasis`, `dakhiil`, and harakat are preserved in details.
- They may produce defects or sanad warnings.
- They do not automatically mean that the poem has multiple qafyas.

The package separates:

- `dominant`: reliable public qafya when authoritative.
- `indexing`: most frequent rawi/cluster for search and filtering.
- `clusters`: grouped rawi-level results.
- `endings`: every analyzed ending.

---

## Quality

```bash
composer validate --strict
vendor/bin/pint --test
vendor/bin/phpstan analyse src
vendor/bin/pest
php benchmarks/basic.php
```

---

## Documentation

- [Arabic documentation](docs/ar.md)
- [English documentation](docs/en.md)

---

## Known Limitations

Arabic qafya often needs context. Undiacritized poetry, ambiguous final haa, extra noon forms, and multi-section poems may require human review. The package exposes `status`, `defects`, `sanad`, `clusters`, and `endings` so applications can decide whether a result is authoritative or needs review.

---
## Why the package is named Mosab

The name **Mosab** was chosen because it carries a direct poetic and historical meaning in Arabic culture.

In Arabic, **مُصعب** suggests strength, firmness, and the ability to handle difficult matters. This fits the package because Arabic qafya detection is not a simple “last-letter” problem. It requires dealing with complex endings, ambiguous letters, weak letters, haa, radf, wasl, khurooj, taasis, dakhiil, and poem-level context.

The name also echoes the spirit of classical Arabic poetry: concise, strong, and rooted in Arabic language heritage.

So the package name reflects its purpose:

- **Strong enough** to handle difficult Arabic qafya cases.
- **Arabic in identity**, not a generic NLP name.
- **Short and memorable** for a PHP package.
- **Suitable for open source**, while still connected to Arabic poetry and language.

In short, **Mosab Qafya Detector** means a serious Arabic-first tool built to handle one of the most difficult parts of Arabic poetry analysis: the qafya.

## لماذا اخترنا اسم مصعب للحزمة؟

اخترنا اسم **مُصعب** لأنه اسم عربي قصير، واضح، وله إيحاء مناسب لطبيعة الحزمة.

فالقافية العربية ليست مسألة سهلة يمكن حلّها بالنظر إلى آخر حرف فقط. تحليل القافية يحتاج التعامل مع حالات دقيقة مثل: الروي، الردف، الوصل، الخروج، التأسيس، الدخيل، الهاء، حروف العلة، والألف المقصورة، مع مراعاة سياق القصيدة لا الكلمة وحدها.

واسم **مُصعب** في العربية يوحي بالقوة والصلابة والقدرة على معالجة  الأمور الصعبة، وهذا يناسب هدف الحزمة: بناء أداة عربية جادة تستطيع التعامل مع صعوبة القافية لا تبسيطها تبسيطًا مخلًا.

كما أن الاسم يحمل طابعًا عربيًا أصيلًا، ويبتعد عن الأسماء التقنية العامة، ليبقى قريبًا من مجال الشعر واللغة العربية.

باختصار، اسم **Mosab Qafya Detector** يعني:

- أداة عربية الهوية.
- مخصصة لمشكلة دقيقة وصعبة في الشعر العربي.
- قصيرة وسهلة التذكر كاسم حزمة.
- مناسبة لمشروع مفتوح المصدر يخدم مطوري الشعر العربي والباحثين في القافية.

لذلك كان اسم **مُصعب** اختيارًا مناسبًا لحزمة تهدف إلى تحليل واحد من أصعب أبواب الشعر العربي: **القافية**.

## Source:
This package started as part of the core qafya analysis work behind [موسوعة الشعراء](https://poetspedia.com), then became open source to help Arabic poetry developers, researchers, and linguists.
