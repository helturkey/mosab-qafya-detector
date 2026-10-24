# Optional linguistic evidence providers

The package adds `Mosab\QafyaDetector\Contracts\LinguisticEvidenceProvider` and the immutable `LinguisticEvidence` value object. An application can adapt an external Arabic diacritizer or morphological analyzer to this interface without adding a hard dependency to Mosab.

Providers are opt-in and must be injected; enabling an option without an injected provider does not download or call an external service. Example for word/ending analysis:

```php
use Mosab\QafyaDetector\QafyaDetector;
use Mosab\QafyaDetector\WordQafyaDetector;
use Mosab\QafyaDetector\Contracts\LinguisticEvidenceProvider;

/** @var LinguisticEvidenceProvider $provider Your adapter, implemented by your application. */
$wordDetector = new WordQafyaDetector(linguisticEvidenceProvider: $provider);
$detector = new QafyaDetector(wordDetector: $wordDetector);

$result = $detector->extract('نهاية', [
    'use_linguistic_evidence' => true,
]);

$evidence = $result->diagnostics['linguistic_evidence'] ?? null;
```

For poem analysis, inject the same configured word detector into `PoemEndingCollector`, then pass that collector into `PoemQafyaDetector`. Applications that do not require provider evidence continue to use the default constructors.

Evidence is returned under `diagnostics.linguistic_evidence`, tagged with its provider and score semantics, and explicitly sets `used_to_override_qafya: false`. The detector does not automatically accept suggested diacritization, morphology, or rawi. Provider exceptions are converted to warnings without exposing the exception message.

Confidence calibration is also opt-in. A profile is considered usable only after it was fitted from reviewed validation data and passed an independent test-split gate. Candidate profiles marked `ready_for_holdout_test` never affect runtime scores. The raw detector `confidence.score` remains unchanged in every case.
