<?php

declare(strict_types=1);

namespace Mosab\QafyaDetector\Support;

/** Immutable runtime options for word and poem analysis. */
final readonly class QafyaOptions
{
    /** @param array<string, mixed>|null $confidenceCalibrationProfile */
    public function __construct(
        public string $mode = 'auto',
        public bool $strictHarakat = true,
        public bool $analyzeSadr = false,
        public bool $detectDefects = true,
        public bool $detectSanad = true,
        public bool $useLinguisticEvidence = false,
        public ?array $confidenceCalibrationProfile = null,
    ) {}

    /** @param array<string, mixed>|self $options */
    public static function normalize(array|self $options = []): self
    {
        if ($options instanceof self) {
            return $options;
        }

        $mode = (string) ($options['mode'] ?? 'auto');
        if (! in_array($mode, ['auto', 'heuristic', 'scholarly'], true)) {
            $mode = 'auto';
        }

        return new self(
            mode: $mode,
            strictHarakat: (bool) ($options['strict_harakat'] ?? $options['strictHarakat'] ?? true),
            analyzeSadr: (bool) ($options['analyze_sadr'] ?? $options['analyzeSadr'] ?? false),
            detectDefects: (bool) ($options['detect_defects'] ?? $options['detectDefects'] ?? true),
            detectSanad: (bool) ($options['detect_sanad'] ?? $options['detectSanad'] ?? true),
            useLinguisticEvidence: (bool) ($options['use_linguistic_evidence'] ?? $options['useLinguisticEvidence'] ?? false),
            confidenceCalibrationProfile: is_array($options['confidence_calibration_profile'] ?? $options['confidenceCalibrationProfile'] ?? null)
                ? ($options['confidence_calibration_profile'] ?? $options['confidenceCalibrationProfile'])
                : null,
        );
    }

    public function resolvedModeFor(string $text): string
    {
        if ($this->mode !== 'auto') {
            return $this->mode;
        }

        // The ending, not a random mark earlier in the hemistich, controls mode.
        $ending = ArabicText::lastArabicWord($text, preserveTashkeel: true) ?? $text;

        return ArabicText::containsMeaningfulTashkeel($ending) ? 'scholarly' : 'heuristic';
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'mode' => $this->mode,
            'strict_harakat' => $this->strictHarakat,
            'analyze_sadr' => $this->analyzeSadr,
            'detect_defects' => $this->detectDefects,
            'detect_sanad' => $this->detectSanad,
            'use_linguistic_evidence' => $this->useLinguisticEvidence,
            'confidence_calibration_profile' => $this->confidenceCalibrationProfile,
        ];
    }
}
