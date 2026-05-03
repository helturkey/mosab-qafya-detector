<?php

declare(strict_types=1);

namespace Mosab\QafyaDetector\Classification;

/**
 * Builds explicit signatures. Cluster signatures may group endings, strict
 * signatures are the poem-level identity used to detect violations.
 */
final class QafyaSignatureBuilder
{
    /**
     * @param  array<string, mixed>  $components
     * @param  array<string, mixed>  $motions
     * @return array{component: ?string, strict: ?string, cluster: ?string, visual: string}
     */
    public function build(array $components, array $motions): array
    {
        $taasis = $this->letter($components, 'taasis');
        $dakhiil = $this->letter($components, 'dakhiil');
        $radf = $this->letter($components, 'radf');
        $rawi = $this->letter($components, 'rawi');
        $wasl = $this->letter($components, 'wasl');
        $khurooj = $this->letter($components, 'khurooj');
        $mujra = is_string($motions['mujra'] ?? null) ? $motions['mujra'] : '?';

        $parts = [];
        if ($taasis !== null) {
            $parts[] = 'T'.$taasis;
        }
        if ($dakhiil !== null) {
            $parts[] = 'D'.$dakhiil;
        }
        if ($radf !== null) {
            $parts[] = 'F'.$radf;
        }
        if ($rawi !== null) {
            $parts[] = 'R'.$rawi;
        }
        if ($wasl !== null) {
            $parts[] = 'W'.$wasl;
        }
        if ($khurooj !== null) {
            $parts[] = 'K'.$khurooj;
        }

        $component = $parts === [] ? null : implode('-', $parts);
        $cluster = implode('-', array_values(array_filter([
            $rawi !== null ? 'R'.$rawi : null,
            $wasl !== null ? 'W'.$wasl : null,
            $khurooj !== null ? 'K'.$khurooj : null,
        ])));

        return [
            'component' => $component,
            'strict' => $component === null ? null : $component.'|mujra='.$mujra,
            'cluster' => $cluster !== '' ? $cluster : $component,
            'visual' => implode('', array_values(array_filter([$taasis, $dakhiil, $radf, $rawi, $wasl, $khurooj], static fn (?string $v): bool => $v !== null))),
        ];
    }

    /** @param array<string, mixed> $components */
    private function letter(array $components, string $key): ?string
    {
        $value = $components[$key] ?? null;
        if (! is_array($value)) {
            return null;
        }
        $letter = $value['letter'] ?? null;

        return is_string($letter) && $letter !== '' ? $letter : null;
    }
}
