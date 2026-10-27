# Optional linguistic evidence providers

The package exposes `Mosab\QafyaDetector\Contracts\LinguisticEvidenceProvider` and the immutable `Mosab\QafyaDetector\Data\LinguisticEvidence` value object. An application can adapt an external Arabic diacritizer or morphological analyzer to this contract without introducing a hard dependency into the core package.

Providers are opt-in and must be injected. Enabling `use_linguistic_evidence` without a provider does not download or call an external service; it adds a diagnostic warning.

## Word-level integration example

```php
use Mosab\QafyaDetector\Contracts\LinguisticEvidenceProvider;
use Mosab\QafyaDetector\QafyaDetector;
use Mosab\QafyaDetector\WordQafyaDetector;

/** @var LinguisticEvidenceProvider $provider Adapter implemented by your application. */
$wordDetector = new WordQafyaDetector(
    linguisticEvidenceProvider: $provider,
);
$detector = new QafyaDetector(wordDetector: $wordDetector);

$result = $detector->extract('نهاية', [
    'use_linguistic_evidence' => true,
]);

$evidence = $result->diagnostics['linguistic_evidence'] ?? null;
```

For poem analysis, inject the configured word detector into `Analysis\PoemEndingCollector`, then pass that collector to `PoemQafyaDetector`. Keep provider wiring explicit in the application container so the dependency and its lifecycle are clear.

## Non-authoritative contract

Provider output is stored under `diagnostics.linguistic_evidence`, with provider identity and score semantics. The package explicitly marks `used_to_override_qafya: false`: suggestions do not silently replace the detector's rawi, component roles, or surface pattern. Provider exceptions become warnings without exposing the exception message to the public result.

A provider may use a network service or process sensitive text, depending on the adapter. Applications are responsible for reviewing that provider's privacy, retention, reliability, and licensing terms.
