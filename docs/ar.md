# توثيق الحزمة

بدأت الحزمة كجزء من نواة تحليل القافية في **[موسوعة الشعراء](https://poetspedia.com)**، ثم فُصلت كحزمة مفتوحة المصدر حتى يستفيد منها مطورو تطبيقات الشعر العربي والباحثون في العروض والقافية. وليشارك الجميع فى تحسينها وإضافة ملاحظاتهم.

---

## 1. ما الذي تفعله الحزمة؟

تحلل الحزمة نهاية البيت أو القصيدة لاستخراج:

- **الروي**: حرف القافية الأساسي.
- **الردف**: حرف المد أو اللين قبل الروي إذا ثبت.
- **الوصل**: حرف يأتي بعد الروي، مثل هاء أو ألف الإطلاق.
- **الخروج**: حرف مد بعد هاء الوصل.
- **التأسيس والدخيل**: عند وجود ألف التأسيس وما بينها وبين الروي.
- **نمط القافية**: مثل `اها` أو `دا` أو `ابه`.
- **تحليل القصيدة**: هل الروي واحد؟ هل القوافي متعددة؟ هل القافية المهيمنة صالحة للعرض العام؟
- **العيوب والسناد**: إشارات مراجعة عندما تظهر مخالفات.

الحزمة لا تزعم أن كل نتيجة حكم عروضي نهائي. هي محرك آلي مضبوط قدر الإمكان، ويُظهر لك مواضع الثقة ومواضع المراجعة.

---

## 2. التثبيت

```bash
composer require helturkey/mosab-qafya-detector
```

```php
use Mosab\QafyaDetector\QafyaDetector;

$detector = QafyaDetector::make();
```

---

## 3. تحليل كلمة أو نهاية واحدة

```php
$result = QafyaDetector::make()->extract('ناداها');

$result->rawi();           // ه
$result->radf();           // ا
$result->wasl();           // ا
$result->patternSurface(); // اها
```

المعنى:

```txt
ناداها
   ا ه ا
   ردف + روي + وصل
```

مثال آخر:

```php
$result = QafyaDetector::make()->extract('كتابُهُ');

$result->rawi();           // ب
$result->radf();           // ا
$result->wasl();           // ه
$result->patternSurface(); // ابه
```

---

## 4. تحليل قصيدة

```php
$analysis = QafyaDetector::make()->analyze([
    'قفْ بالديار وصحْ إلى بيداها',
    'فعسى الديار تجيبُ منْ ناداها',
    'دارٌ يفوحُ المِسْك من عَرَصاتِها',
    'والعودُ والندُّ الذكيُّ جناها',
]);

$analysis->status();             // ok
$analysis->rawi();               // ه
$analysis->qafyaPattern();       // اها
$analysis->hasMultipleQafyas();  // false
$analysis->dominantRatio();      // 1.0
```

---

## 5. معنى status

### `ok`

النتيجة مستقرة، ولا توجد إشارات مراجعة مؤثرة.

### `review`

الحزمة وجدت نتيجة، لكنها لاحظت شيئًا يستحق المراجعة، مثل تعدد الروي، وجود قوافٍ فرعية، سناد أو عيب، انخفاض نسبة القافية المهيمنة، أو تقدير حدود القافية بسبب نقص التشكيل.

### `partial`

المدخل غير كافٍ أو غير مزدوج، لكن الحزمة رجعت استجابة منظمة بدل الانهيار.

### `error`

خطأ لا يمكن معه التحليل.

---

## 6. سياسة القصيدة: الروي هو الهوية العامة

في التحليل العام للقصيدة، تعتمد الحزمة على **الروي** لتحديد وحدة القافية.

لا تجعل الحزمة الردف أو الوصل أو الخروج سببًا مباشرًا لتعدد القافية. هذه المكوّنات تظهر في التفاصيل، وقد تُنتج سنادًا أو عيبًا، لكنها ليست بديلًا عن الروي.

مثال:

```txt
جديدا / يعودا / جيدا / جودا
```

التحليل العام:

```txt
rawi = د
wasl = ا
pattern = دا
```

رغم أن ما قبل الدال يتعاقب بين `ي` و`و`.

---

## 7. dominant و indexing

هناك فرق مهم بين:

### dominant

القافية العامة التي تعرضها الحزمة إذا كانت موثوقة.

### indexing

أكثر روي أو cluster تكرارًا، يصلح للبحث والفهرسة حتى لو لم يكن حكمًا قافويًا نهائيًا.

عند القصائد متعددة القوافي، قد يكون:

```php
$analysis->dominant; // null
```

لكن تبقى البيانات موجودة في:

```php
$analysis->indexing;
$analysis->clusters();
$analysis->endings();
```

---

## 8. hasMultipleQafyas

```php
$analysis->hasMultipleQafyas();
```

تعني أن القصيدة فيها أكثر من روي على مستوى التحليل العام.

لا تعني بالضرورة أن الحزمة فشلت. بل تعني أن على التطبيق ألا يعرض رويًا واحدًا على أنه حكم نهائي للقصيدة.

---

## 9. القواعد المهمة

### الألف والألف المقصورة

إذا كان ما قبل الألف أو المقصورة متنوعًا، قد تكون الألف أو المقصورة محور القافية:

```txt
هدى / غلا / سرى / ترى
```

أما إذا كان ما قبلها ثابتًا، فالروي هو الحرف السابق، والألف أو المقصورة وصل صوتي:

```txt
غضّا / نفضا / عرضا / ترضى
```

### الهاء

الهاء ليست دائمًا رويًا وليست دائمًا وصلًا:

```txt
كتابه  => الروي ب، والهاء وصل
ينساه  => الهاء روي، والألف ردف
ناداها => الألف ردف، الهاء روي، الألف وصل
```

### الردف

يدخل الردف في النمط العام إذا كان ثابتًا:

```txt
ناداها / جناها / تراها
pattern = اها
```

ولا يدخل الردف المتعاقب في النمط العام إذا تعاقب `و/ي`:

```txt
جديدا / يعودا / جيدا / جودا
pattern = دا
```

### النون الزائدة

توجد معالجة محافظة لنون التوكيد، نون النسوة، ونون الترنم أو التنوين المكتوبة. هذه المنطقة ما زالت مرشحة لمزيد من التحسين لأنها تحتاج أحيانًا سياقًا صرفيًا.

---

## 10. QafyaResult

يمثل نتيجة كلمة أو نهاية واحدة.

```php
$result->status();
$result->rawi();
$result->radf();
$result->wasl();
$result->khurooj();
$result->taasis();
$result->dakhiil();
$result->segmentSurface();
$result->patternSurface();
$result->toArray();
```

---

## 11. PoemQafyaAnalysis

يمثل نتيجة القصيدة.

```php
$analysis->status();
$analysis->rawi();
$analysis->qafyaPattern();
$analysis->hasMultipleQafyas();
$analysis->dominantRatio();
$analysis->clusters();
$analysis->endings();
$analysis->defects();
$analysis->sanad();
$analysis->toArray();
```

---

## 12. Laravel

```php
use Mosab\QafyaDetector\Laravel\Facades\QafyaDetector;

$result = QafyaDetector::extract('كتابُهُ');
$analysis = QafyaDetector::analyze($poem);
```

```bash
php artisan vendor:publish --tag=mosab-qafya-detector-config
```

---

## 13. Enums

كل Enum يحتوي:

```php
$enum->label();
$enum->description();
```

`label()` تعطي الاسم العربي، و`description()` تعطي شرحًا قصيرًا.

---

## 14. الاختبارات والجودة

```bash
composer validate --strict
vendor/bin/pint --test
vendor/bin/phpstan analyse src
vendor/bin/pest
```

الاختبارات تغطي: تحليل الكلمة، تحليل القصيدة، الهاء، الألف والمقصورة، الردف، القوافي المتعددة، fixtures من Poetspedia، Enums، وسياسة صلاحية الروي.

---

## 15. Benchmarks

```bash
php benchmarks/basic.php
php -d opcache.enable_cli=1 benchmarks/basic.php
```

الصورة:

![Benchmark](assets/benchmark-basic.svg)

---

## 16. القيود المعروفة

- النص غير المشكول يعتمد على heuristics.
- بعض حالات الهاء تحتاج قرائن أوسع.
- النون الزائدة تحتاج تحسينات صرفية.
- القصائد متعددة المقاطع تحتاج تحليل sections أدق.
- النتائج الآلية لا تغني عن التحقيق البشري في الحالات المشكلة.

---

## 17. المراجع والشكر

أقدم الشكر لهذه الكتب فى فهم والتحقق من بعض القواعد:

- https://archive.org/stream/alqafeya_fi_al-arwd_wa_al-adab/alqafeya_fi_al-arwd_wa_al-adab_djvu.txt
- https://drive.uqu.edu.sa/_/hzrshood/files/%D9%85%D9%82%D8%B1%D8%B1%20%D8%B9%D9%84%D9%85%20%D8%A7%D9%84%D9%82%D8%A7%D9%81%D9%8A%D8%A9.pdf
- https://dn790008.ca.archive.org/0/items/waq2224/2224.pdf
- https://elibrary.mediu.edu.my/books/SDL1337.pdf
- https://journals.najah.edu/media/journals/full_texts/4_42ievmN.pdf
- https://etheses.uin-malang.ac.id/58385/1/07310053.pdf
- https://shamela.ws/book/10860/130
- https://www.uomustansiriyah.edu.iq/media/lectures/9/9_2018_12_30!12_44_31_AM.pdf
- https://archive.org/stream/elhilalymohamad_gmail_20170302_0535/%D9%81%D9%8A%20%D8%B9%D9%84%D9%85%20%D8%A7%D9%84%D9%82%D8%A7%D9%81%D9%8A%D8%A9%20-%20%D8%AF.%20%D8%A3%D9%85%D9%8A%D9%86%20%D8%B9%D9%84%D9%8A%20%D8%A7%D9%84%D8%B3%D9%8A%D8%AF_djvu.txt
- https://drive.uqu.edu.sa/_/hashangity/files/Qafia232.pdf
- https://archive.org/stream/3lm-al3arood-al3atiq/%D8%B9%D9%84%D9%85%20%D8%A7%D9%84%D8%B9%D8%B1%D9%88%D8%B6%20%D9%88%D8%A7%D9%84%D9%82%D8%A7%D9%81%D9%8A%D8%A9%20-%20%D8%B9%D8%A8%D8%AF%D8%A7%D9%84%D8%B9%D8%B2%D9%8A%D8%B2%20%D8%B9%D8%AA%D9%8A%D9%82_djvu.txt
- https://www.islamicbook.ws/adab/alqwafi-lltnwkhi.pdf
- https://drive.uqu.edu.sa/_/amamry/files/232_.pdf
- https://ia601404.us.archive.org/7/items/elhilalymohamad_gmail_20170415_0410/%D9%85%D9%8A%D8%B2%D8%A7%D9%86%20%D8%A7%D9%84%D8%B0%D9%87%D8%A8%20%D9%81%D9%8A%20%D8%B5%D9%86%D8%A7%D8%B9%D8%A9%20%D8%B4%D8%B9%D8%B1%20%D8%A7%D9%84%D8%B9%D8%B1%D8%A8.pdf
- https://arxiv.org/pdf/2307.06218