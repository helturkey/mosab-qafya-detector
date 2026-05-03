<?php

declare(strict_types=1);

namespace Mosab\QafyaDetector\Analysis;

/**
 * Builds poem-level obligations from the first valid qafya reference.
 */
final class QafyaObligationResolver
{
    /**
     * @param  array<string, mixed>  $reference
     * @return array<string, array<string, mixed>>
     */
    public function resolve(array $reference): array
    {
        $components = is_array($reference['qafya']['components'] ?? null) ? $reference['qafya']['components'] : [];
        $rawi = $this->letter($components, 'rawi');
        $radf = $this->letter($components, 'radf');
        $taasis = $this->letter($components, 'taasis');
        $wasl = $this->letter($components, 'wasl');
        $khurooj = $this->letter($components, 'khurooj');
        $mujra = is_string($reference['qafya']['motions']['mujra'] ?? null) ? $reference['qafya']['motions']['mujra'] : null;

        return [
            'rawi' => ['required' => $rawi !== null, 'letter' => $rawi],
            'mujra' => ['required' => $mujra !== null, 'value' => $mujra],
            'radf' => ['required' => $radf !== null, 'letter' => $radf, 'family' => $this->radfFamily($radf), 'allowed' => $this->allowedRadf($radf)],
            'taasis' => ['required' => $taasis !== null, 'letter' => $taasis],
            'wasl' => ['required' => $wasl !== null, 'letter' => $wasl],
            'khurooj' => ['required' => $khurooj !== null, 'letter' => $khurooj],
        ];
    }

    /** @param array<string, mixed> $components */
    private function letter(array $components, string $key): ?string
    {
        return is_array($components[$key] ?? null) && is_string($components[$key]['letter'] ?? null) ? $components[$key]['letter'] : null;
    }

    private function radfFamily(?string $radf): ?string
    {
        return match ($radf) {
            'ا', 'ى' => 'alif',
            'و', 'ي' => 'waw_yaa',
            default => null,
        };
    }

    /** @return list<string> */
    private function allowedRadf(?string $radf): array
    {
        return match ($this->radfFamily($radf)) {
            'alif' => ['ا'],
            'waw_yaa' => ['و', 'ي'],
            default => [],
        };
    }
}
