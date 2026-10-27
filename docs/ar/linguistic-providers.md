# مزودات الأدلة اللغوية الاختيارية

توفر الحزمة العقد `Mosab\QafyaDetector\Contracts\LinguisticEvidenceProvider` وكائن البيانات غير القابل للتعديل `Mosab\QafyaDetector\Data\LinguisticEvidence`. ويمكن للتطبيق تكييف مشكّل آلي أو محلل صرفي عربي خارجي مع هذا العقد دون إضافة اعتماد ثابت إلى نواة الحزمة.

المزودات اختيارية ويجب حقنها صراحة. تفعيل `use_linguistic_evidence` دون مزود لا ينزّل خدمة ولا يستدعيها؛ بل يضيف تحذيراً تشخيصياً.

## مثال تكامل على مستوى الكلمة

```php
use Mosab\QafyaDetector\Contracts\LinguisticEvidenceProvider;
use Mosab\QafyaDetector\QafyaDetector;
use Mosab\QafyaDetector\WordQafyaDetector;

/** @var LinguisticEvidenceProvider $provider محول تنفذه في التطبيق. */
$wordDetector = new WordQafyaDetector(
    linguisticEvidenceProvider: $provider,
);
$detector = new QafyaDetector(wordDetector: $wordDetector);

$result = $detector->extract('نهاية', [
    'use_linguistic_evidence' => true,
]);

$evidence = $result->diagnostics['linguistic_evidence'] ?? null;
```

لتحليل القصيدة، مرّر محلل الكلمات المهيأ إلى `Analysis\PoemEndingCollector`، ثم مرّر المجمع إلى `PoemQafyaDetector`. من الأفضل إبقاء تركيب المزود واضحاً في حاوية التطبيق حتى تكون الاعتمادية ودورة حياتها معلومتين.

## عقد غير سلطوي

تُحفظ نتيجة المزود في `diagnostics.linguistic_evidence` مع هوية المزود ودلالة الدرجة. وتضبط الحزمة `used_to_override_qafya: false`؛ فلا تستبدل اقتراحات المزود الروي أو أدوار المكونات أو النمط الظاهر تلقائياً. وتتحول استثناءات المزود إلى تحذيرات دون كشف نص الاستثناء في النتيجة العامة.

قد يرسل المزود الخارجي النص إلى خدمة شبكية أو يعالجه وفق سياسة احتفاظ خاصة به. يتحمل التطبيق مسؤولية مراجعة الخصوصية والاحتفاظ والاعتمادية والترخيص لدى المزود.
