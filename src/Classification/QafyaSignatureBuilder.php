<?php

declare(strict_types=1);

namespace Mosab\QafyaDetector\Classification;

use Mosab\QafyaDetector\Support\ArabicLetters;
use Mosab\QafyaDetector\Support\QafyaSurfaceFormatter;
use Mosab\QafyaDetector\Text\PhoneticText;

/**
 * Builds explicit signatures. Cluster signatures may group endings, strict
 * signatures are the poem-level identity used to detect violations.
 */
final class QafyaSignatureBuilder
{
    /**
     * @param  array<string, mixed>  $components
     * @param  array<string, mixed>  $motions
     * @param  array<string, mixed>  $context  optional word-level context: 'word' (last word,
     *                                         source spelling, may be empty), 'rawi_index' (ordinal of the rawi
     *                                         in the normalized letters of 'word') and
     *                                         'surface_pattern' (custom visible pattern). When
     *                                         'word' is given, a 'display' entry is added.
     * @return array{component: ?string, strict: ?string, cluster: ?string, visual: string, display?: array{surface: string, tail: array{surface: ?string, prefix: ?string, extended: bool, rule: string}}}
     */
    public function build(array $components, array $motions, array $context = []): array
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

        $visual = implode('', array_values(array_filter([$taasis, $dakhiil, $radf, $rawi, $wasl, $khurooj], static fn (?string $v): bool => $v !== null)));

        $signatures = [
            'component' => $component,
            'strict' => $component === null ? null : $component.'|mujra='.$mujra,
            'cluster' => $cluster !== '' ? $cluster : $component,
            'visual' => $visual,
        ];

        if (is_string($context['word'] ?? null)) {
            $signatures['display'] = $this->display($components, $context);
        }

        return $signatures;
    }

    /**
     * Final visible surface and shared tail for one word. Same context as build().
     *
     * @param  array<string, mixed>  $components
     * @param  array<string, mixed>  $context
     * @return array{surface: string, tail: array{surface: ?string, prefix: ?string, extended: bool, rule: string}}
     */
    public function display(array $components, array $context): array
    {
        $taasis = $this->letter($components, 'taasis');
        $dakhiil = $this->letter($components, 'dakhiil');
        $radf = $this->letter($components, 'radf');
        $rawi = $this->letter($components, 'rawi');
        $wasl = $this->letter($components, 'wasl');
        $khurooj = $this->letter($components, 'khurooj');
        $visual = implode('', array_values(array_filter([$taasis, $dakhiil, $radf, $rawi, $wasl, $khurooj], static fn (?string $v): bool => $v !== null)));

        $word = is_string($context['word'] ?? null) ? $context['word'] : '';
        $customSurface = $context['surface_pattern'] ?? null;
        $hasCustomSurface = is_string($customSurface) && $customSurface !== '';
        $surface = QafyaSurfaceFormatter::preserveTaaMarbuta(
            $hasCustomSurface ? $customSurface : $visual,
            $word,
            $rawi,
            $radf,
            $wasl,
        );
        $rawiIndex = $context['rawi_index'] ?? null;

        return [
            'surface' => $surface,
            'tail' => $this->tail($word, is_int($rawiIndex) ? $rawiIndex : null, $surface, $taasis, $dakhiil, $radf, $hasCustomSurface),
        ];
    }

    /**
     * The shared tail: the surface plus the explicitly moving letter right before
     * a plain rawi (ذَهَبْ => هب). Nothing is guessed: no letter is added when the
     * ending has taasis, dakhiil or radf, when a custom surface is used, or when
     * the previous letter is not explicitly moving.
     *
     * @return array{surface: ?string, prefix: ?string, extended: bool, rule: string}
     */
    private function tail(string $word, ?int $rawiIndex, string $surface, ?string $taasis, ?string $dakhiil, ?string $radf, bool $hasCustomSurface): array
    {
        if ($surface === '') {
            return $this->plainTail(null, 'no_pattern_surface');
        }

        foreach (['taasis' => $taasis, 'dakhiil' => $dakhiil, 'radf' => $radf] as $role => $letter) {
            if ($letter !== null) {
                return $this->plainTail($surface, 'ending_has_'.$role);
            }
        }

        if ($hasCustomSurface) {
            return $this->plainTail($surface, 'custom_surface_pattern');
        }

        if ($rawiIndex === null || $rawiIndex < 1) {
            return $this->plainTail($surface, 'no_letter_before_rawi');
        }

        $phonetic = new PhoneticText($word);
        $previousIndex = $rawiIndex - 1;
        $previous = $phonetic->normalizedLetters()[$previousIndex] ?? null;
        if ($previous === null || ! ArabicLetters::isArabicLetter($previous)) {
            return $this->plainTail($surface, 'no_letter_before_rawi');
        }

        $mark = $phonetic->harakaAtLetterIndex($previousIndex)['mark'];
        $isMoving = $mark !== null
            && (ArabicLetters::isShortHaraka($mark) || in_array($mark, ArabicLetters::TANWEEN, true));
        if (! $isMoving) {
            return $this->plainTail($surface, 'previous_letter_not_explicitly_moving');
        }

        return [
            'surface' => $previous.$surface,
            'prefix' => $previous,
            'extended' => true,
            'rule' => 'moving_letter_before_rawi',
        ];
    }

    /** @return array{surface: ?string, prefix: ?string, extended: bool, rule: string} */
    private function plainTail(?string $surface, string $rule): array
    {
        return ['surface' => $surface, 'prefix' => null, 'extended' => false, 'rule' => $rule];
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
