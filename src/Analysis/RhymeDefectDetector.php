<?php

declare(strict_types=1);

namespace Mosab\QafyaDetector\Analysis;

/**
 * Detects practical qafya violations against the reference ending.
 */
final class RhymeDefectDetector
{
    /**
     * @param  array<string, mixed>  $reference
     * @param  list<array<string, mixed>>  $endings
     * @return list<array<string, mixed>>
     */
    public function detect(array $reference, array $endings): array
    {
        $defects = [];
        /** @var array<string, int> $seenWords */
        $seenWords = [];
        $ref = $this->parts($reference['result'] ?? $reference);

        foreach ($endings as $ending) {
            $result = is_array($ending['result'] ?? null) ? $ending['result'] : [];
            $parts = $this->parts($result);
            $bayt = (int) ($ending['bayt'] ?? 0);

            if ($ref['rawi'] !== null && $parts['rawi'] !== null && $parts['rawi'] !== $ref['rawi']) {
                $defects[] = $this->defect('rawi_mismatch', 'اختلاف الروي', 'error', $bayt, ['rawi' => $ref['rawi']], ['rawi' => $parts['rawi']]);
            }

            if ($ref['rawi'] === $parts['rawi'] && $ref['mujra'] !== null && $parts['mujra'] !== null && $parts['mujra'] !== $ref['mujra']) {
                $defects[] = $this->defect('iqwa', 'الإقواء', 'error', $bayt, ['mujra' => $ref['mujra']], ['mujra' => $parts['mujra']]);
            }

            if ($ref['radf_family'] !== null && $parts['radf_family'] !== null && $parts['radf_family'] !== $ref['radf_family']) {
                $defects[] = $this->defect('sanad_radf', 'سناد الردف', 'error', $bayt, ['radf_family' => $ref['radf_family']], ['radf_family' => $parts['radf_family']]);
            } elseif ($ref['radf_family'] === 'alif' && $parts['radf'] !== null && $parts['radf'] !== 'ا') {
                $defects[] = $this->defect('sanad_radf', 'سناد الردف', 'error', $bayt, ['radf' => 'ا'], ['radf' => $parts['radf']]);
            } elseif ($ref['radf'] !== null && $parts['radf'] === null) {
                $defects[] = $this->defect('sanad_radf', 'سناد الردف', 'error', $bayt, ['radf' => $ref['radf']], ['radf' => null]);
            }

            if (($ref['taasis'] !== null) !== ($parts['taasis'] !== null)) {
                $defects[] = $this->defect('sanad_taasis', 'سناد التأسيس', 'error', $bayt, ['taasis' => $ref['taasis']], ['taasis' => $parts['taasis']]);
            }

            foreach (['wasl' => 'اختلاف الوصل', 'khurooj' => 'اختلاف الخروج'] as $key => $name) {
                if ($ref[$key] !== null && $parts[$key] !== null && $parts[$key] !== $ref[$key]) {
                    $defects[] = $this->defect($key.'_mismatch', $name, 'warning', $bayt, [$key => $ref[$key]], [$key => $parts[$key]]);
                }
            }

            $word = is_string($result['input']['normalized'] ?? null) ? $result['input']['normalized'] : null;
            if ($word !== null && $word !== '') {
                if (isset($seenWords[$word]) && ($bayt - $seenWords[$word]) < 7) {
                    $defects[] = $this->defect('ita', 'الإيطاء', 'warning', $bayt, ['distance_at_least' => 7], ['repeated_word' => $word, 'previous_bayt' => $seenWords[$word]]);
                }
                $seenWords[$word] = $bayt;
            }
        }

        return $defects;
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array{rawi: ?string, mujra: ?string, radf: ?string, radf_family: ?string, taasis: ?string, wasl: ?string, khurooj: ?string}
     */
    private function parts(array $result): array
    {
        $components = is_array($result['qafya']['components'] ?? null) ? $result['qafya']['components'] : [];
        $motions = is_array($result['qafya']['motions'] ?? null) ? $result['qafya']['motions'] : [];
        $radf = $this->letter($components, 'radf');

        return [
            'rawi' => $this->letter($components, 'rawi'),
            'mujra' => is_string($motions['mujra'] ?? null) ? $motions['mujra'] : null,
            'radf' => $radf,
            'radf_family' => match ($radf) {
                'ا', 'ى' => 'alif', 'و', 'ي' => 'waw_yaa', default => null
            },
            'taasis' => $this->letter($components, 'taasis'),
            'wasl' => $this->letter($components, 'wasl'),
            'khurooj' => $this->letter($components, 'khurooj'),
        ];
    }

    /** @param array<string, mixed> $components */
    private function letter(array $components, string $key): ?string
    {
        return is_array($components[$key] ?? null) && is_string($components[$key]['letter'] ?? null) ? $components[$key]['letter'] : null;
    }

    /**
     * @param  array<string, mixed>  $expected
     * @param  array<string, mixed>  $actual
     * @return array{type: string, arabic_name: string, severity: string, bayt: int, expected: array<string, mixed>, actual: array<string, mixed>}
     */
    private function defect(string $type, string $arabicName, string $severity, int $bayt, array $expected, array $actual): array
    {
        return [
            'type' => $type,
            'arabic_name' => $arabicName,
            'severity' => $severity,
            'bayt' => $bayt,
            'expected' => $expected,
            'actual' => $actual,
        ];
    }
}
