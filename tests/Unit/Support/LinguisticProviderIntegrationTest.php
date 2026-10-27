<?php

declare(strict_types=1);

use Mosab\QafyaDetector\Contracts\LinguisticEvidenceProvider;
use Mosab\QafyaDetector\Contracts\PoemQafyaDetectorContract;
use Mosab\QafyaDetector\Contracts\QafyaDetectorContract;
use Mosab\QafyaDetector\Contracts\WordQafyaDetectorContract;
use Mosab\QafyaDetector\Data\LinguisticEvidence;
use Mosab\QafyaDetector\PoemQafyaDetector;
use Mosab\QafyaDetector\QafyaDetector;
use Mosab\QafyaDetector\WordQafyaDetector;

/** A provider that records its calls and returns fixed evidence. */
final class RecordingEvidenceProvider implements LinguisticEvidenceProvider
{
    public int $calls = 0;

    /** @var list<array<string, mixed>> */
    public array $contexts = [];

    public function analyze(string $text, array $context = []): LinguisticEvidence
    {
        $this->calls++;
        $this->contexts[] = $context;

        return new LinguisticEvidence('fake-provider', suggestions: [['text' => 'كِتَابُهُ']], confidence: 0.9);
    }
}

/** A provider that always fails with a message that must never reach the output. */
final class ThrowingEvidenceProvider implements LinguisticEvidenceProvider
{
    public function analyze(string $text, array $context = []): LinguisticEvidence
    {
        throw new RuntimeException('secret provider detail');
    }
}

it('attaches provider evidence without changing the qafya decision', function (): void {
    $provider = new RecordingEvidenceProvider;
    $withEvidence = (new WordQafyaDetector(linguisticEvidenceProvider: $provider))->detect('كتابه', ['use_linguistic_evidence' => true]);
    $without = (new WordQafyaDetector)->detect('كتابه');

    expect($provider->calls)->toBe(1)
        ->and($provider->contexts[0])->toBe(['mode' => 'heuristic', 'source' => 'qafya_ending', 'role' => 'supplemental_evidence_only'])
        ->and($withEvidence->diagnostics['linguistic_evidence']['provider'])->toBe('fake-provider')
        ->and($withEvidence->diagnostics['linguistic_evidence']['used_to_override_qafya'])->toBeFalse()
        ->and($withEvidence->rawi())->toBe($without->rawi())
        ->and($withEvidence->radf())->toBe($without->radf())
        ->and($withEvidence->wasl())->toBe($without->wasl());
});

it('does not call the provider unless linguistic evidence is requested', function (): void {
    $provider = new RecordingEvidenceProvider;
    $result = (new WordQafyaDetector(linguisticEvidenceProvider: $provider))->detect('كتابه');

    expect($provider->calls)->toBe(0)
        ->and($result->diagnostics['linguistic_evidence'])->toBeNull();
});

it('warns when evidence is requested but no provider is configured', function (): void {
    $result = (new WordQafyaDetector)->detect('كتابه', ['use_linguistic_evidence' => true]);
    $types = array_map(static fn (array $warning): string => (string) ($warning['type'] ?? ''), $result->warnings());

    expect($types)->toContain('linguistic_evidence_provider_unavailable')
        ->and($result->diagnostics['linguistic_evidence'])->toBeNull()
        ->and($result->rawi())->toBe('ب');
});

it('contains a failing provider and does not leak its message', function (): void {
    $result = (new WordQafyaDetector(linguisticEvidenceProvider: new ThrowingEvidenceProvider))->detect('كتابه', ['use_linguistic_evidence' => true]);
    $failed = array_values(array_filter(
        $result->warnings(),
        static fn (array $warning): bool => ($warning['type'] ?? null) === 'linguistic_evidence_provider_failed',
    ));

    expect($failed)->toHaveCount(1)
        ->and($failed[0]['exception'])->toBe(RuntimeException::class)
        ->and(json_encode($result->toArray()))->not->toContain('secret provider detail')
        ->and($result->rawi())->toBe('ب');
});

it('validates the provider-reported confidence range', function (): void {
    expect(fn () => new LinguisticEvidence('p', confidence: 1.5))->toThrow(InvalidArgumentException::class)
        ->and(fn () => new LinguisticEvidence('p', confidence: -0.1))->toThrow(InvalidArgumentException::class)
        ->and((new LinguisticEvidence('p', confidence: 0.0))->confidence)->toBe(0.0)
        ->and((new LinguisticEvidence('p', confidence: 1.0))->confidence)->toBe(1.0)
        ->and((new LinguisticEvidence('p'))->confidence)->toBeNull();
});

it('serializes provider evidence as non-authoritative', function (): void {
    $array = (new LinguisticEvidence('p', warnings: ['w']))->toArray();

    expect($array)->toBe([
        'provider' => 'p',
        'suggestions' => [],
        'morphology' => [],
        'confidence' => null,
        'confidence_semantics' => 'provider_reported_score_not_verified_probability',
        'used_to_override_qafya' => false,
        'warnings' => ['w'],
    ]);
});

it('implements the public detector contracts', function (): void {
    expect(QafyaDetector::make())->toBeInstanceOf(QafyaDetectorContract::class)
        ->and(new WordQafyaDetector)->toBeInstanceOf(WordQafyaDetectorContract::class)
        ->and(new PoemQafyaDetector)->toBeInstanceOf(PoemQafyaDetectorContract::class);
});
