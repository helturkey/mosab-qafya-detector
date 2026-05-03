<?php

declare(strict_types=1);

namespace Mosab\QafyaDetector\Detection;

use Mosab\QafyaDetector\Classification\RawiEligibilityPolicy;
use Mosab\QafyaDetector\Enums\QafyaCategory;
use Mosab\QafyaDetector\Enums\QafyaComponent;
use Mosab\QafyaDetector\Support\ArabicLetters;
use Mosab\QafyaDetector\Support\ArabicText;

/**
 * Detects the six qafya letters from a final word or ending.
 */
final class ComponentDetector
{
    public function __construct(
        private readonly HarakaLocator $harakaLocator = new HarakaLocator,
        private readonly RawiEligibilityPolicy $eligibility = new RawiEligibilityPolicy,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function detect(string $ending): array
    {
        $word = ArabicText::lastArabicWord($ending, preserveTashkeel: true) ?? $ending;
        $normalized = ArabicText::normalizeForQafya($word);
        $chars = ArabicText::chars($normalized);
        /** @var list<array<string, mixed>> $trace */
        $trace = [];
        /** @var list<array<string, mixed>> $warnings */
        $warnings = [];
        if (! ArabicText::containsMeaningfulTashkeel($word)) {
            $warnings[] = ['type' => 'undiacritized_ending', 'message' => 'Haraka-sensitive fields are estimated because the ending is not fully diacritized.'];
        }
        /** @var list<array<string, mixed>> $ambiguities */
        $ambiguities = [];

        $rawi = null;
        $wasl = null;
        $khurooj = null;
        $category = QafyaCategory::Muqayyada->value;
        $forcedRadf = null;
        $suppressTaasis = false;
        $surfacePattern = null;
        $forcedRawiSurface = null;

        if ($chars === []) {
            return $this->empty($word, $normalized, [['type' => 'empty_ending', 'message' => 'No Arabic ending was found.']]);
        }

        $last = count($chars) - 1;
        $final = $chars[$last];
        $prev = $chars[$last - 1] ?? null;
        $prev2 = $chars[$last - 2] ?? null;

        if ($last >= 2 && in_array($final, ['ا', 'ى'], true) && $prev === 'و' && ArabicLetters::normalizeHamza($prev2) === 'ء') {
            $rawiIndex = $last - 2;
            $rawi = 'ء';
            $wasl = 'و';
            $khurooj = 'ا';
            $category = QafyaCategory::Mutlaqa->value;
            $trace[] = ['rule' => 'final_hamza_waw_alef', 'decision' => 'rawi_hamza_wasl_waw_khurooj_alef'];
        } elseif ($last >= 1 && $this->isFinalMaddLike($final) && ArabicLetters::isHaa($prev)) {
            if ($this->isMaddOrAlefMadda($prev2)) {
                $rawiIndex = $last - 1;
                $rawi = 'ه';
                $wasl = $this->canonicalMaddLike($final);
                $forcedRadf = $this->canonicalMaddLike($prev2);
                $category = QafyaCategory::Mutlaqa->value;
                $trace[] = ['rule' => 'haa_preceded_by_madd_with_final_madd', 'decision' => 'haa_rawi_final_madd_wasl'];
            } else {
                $rawiIndex = $last - 2;
                $rawi = $rawiIndex >= 0 ? ArabicText::normalizeForRawi($chars[$rawiIndex]) : null;
                $wasl = 'ه';
                $khurooj = $this->canonicalMaddLike($final);
                $category = QafyaCategory::Mutlaqa->value;
                $trace[] = ['rule' => 'haa_with_final_madd', 'decision' => 'previous_letter_rawi_haa_wasl_final_madd_khurooj'];
            }
        } elseif ($final === 'ة' && $this->hasExplicitMovingTaaMarbuta($word)) {
            /*
             * الصلاةِ / قراةِ / بزاةِ / النجاةِ:
             *
             * A visibly vocalized tāʾ marbūṭa is pronounced as ت in qafya,
             * not as pause ه. This branch must run before generic haa/
             * taa-marbuta pause logic.
             */
            $rawiIndex = $last;
            $rawi = 'ت';
            $forcedRawiSurface = 'ة';
            $wasl = null;
            $category = QafyaCategory::Muqayyada->value;

            $trace[] = [
                'rule' => 'vocalized_taa_marbuta_as_taa_rawi',
                'decision' => 'taa_marbuta_with_explicit_haraka_is_taa_rawi',
            ];
        } elseif (ArabicLetters::isHaa($final)) {
            $haaContext = $this->resolveBareHaaContext($word, $chars, $last);
            $rawiIndex = $haaContext['rawi_index'];
            $rawi = $rawiIndex >= 0 && isset($chars[$rawiIndex])
                ? ArabicText::normalizeForRawi($chars[$rawiIndex])
                : null;
            $wasl = $haaContext['wasl'];
            $forcedRadf = $haaContext['forced_radf'] ?? null;
            $suppressTaasis = ($haaContext['suppress_taasis'] ?? false) === true;
            $category = $haaContext['category'];
            $trace[] = ['rule' => $haaContext['rule'], 'decision' => $haaContext['decision']];

            if (($haaContext['ambiguous'] ?? false) === true) {
                $ambiguities[] = ['type' => 'haa_role_needs_poem_context', 'letter' => 'ه'];
            }
        } elseif ($final === 'ى' && $last >= 1 && ArabicText::isHamzaSeat($prev)) {
            /*
             * عزائى / بسمائى / دائى:
             *
             * In modern writing, final ى after hamza-seat often stands for final ي:
             * عزائى = عزائي, دائى = دائي.
             *
             * This is a narrow rule. It must not affect سعى / رمى / هدى.
             */
            $rawiIndex = $last - 1;
            $rawi = ArabicText::canonicalRawiIdentity($chars[$rawiIndex]);
            $wasl = 'ي';
            $category = QafyaCategory::Mutlaqa->value;
            $surfacePattern = $this->hamzaSeatSurfacePattern($chars, $rawiIndex);

            $trace[] = [
                'rule' => 'final_maqsura_after_hamza_seat',
                'decision' => 'hamza_rawi_final_maqsura_as_yaa_wasl',
                'surface_pattern' => $surfacePattern,
            ];
        } elseif ($final === 'ى') {
            $rawiIndex = $last;
            $rawi = 'ى';
            $wasl = null;
            $category = QafyaCategory::Muqayyada->value;
            $trace[] = ['rule' => 'final_alef_maqsura', 'decision' => 'written_alef_maqsura_rawi'];
        } elseif ($this->isFinalAlefLike($final)) {
            $rawiIndex = $last - 1;
            $rawi = $rawiIndex >= 0 ? ArabicText::normalizeForRawi($chars[$rawiIndex]) : null;
            $wasl = 'ا';
            $category = QafyaCategory::Mutlaqa->value;
            $surfacePattern = $this->finalAlefMaddaSurfacePattern($chars, $rawiIndex);
            $trace[] = $surfacePattern !== null
                ? ['rule' => 'final_alef_madda', 'decision' => 'final_alef_madda_wasl_previous_letter_rawi', 'surface_pattern' => $surfacePattern]
                : ['rule' => 'final_alef', 'decision' => 'final_alef_wasl_previous_letter_rawi'];
        } elseif (in_array($final, ['و', 'ي'], true) && $last >= 1 && $this->isMaddOrAlefMadda($prev)) {
            /*
             * ثاوِ / واوِ / نايُ / ناي:
             *
             * Final و/ي after alef is often the committed rawi, especially
             * when visibly vocalized or in short lexical endings. Do not turn
             * it into wasl. This does not affect يدعو / قاضي, because their
             * final و/ي is not preceded by alef.
             */
            $rawiIndex = $last;
            $rawi = $final;
            $wasl = null;
            $forcedRadf = 'ا';
            $category = QafyaCategory::Muqayyada->value;

            $trace[] = [
                'rule' => 'final_weak_after_alef_as_rawi',
                'decision' => 'final_waw_yaa_after_alef_kept_as_rawi',
            ];
        } elseif (in_array($final, ['و', 'ي'], true) && $last >= 1) {
            $rawiIndex = $last - 1;
            $rawi = ArabicText::normalizeForRawi($chars[$rawiIndex]);
            $wasl = $final;
            $category = QafyaCategory::Mutlaqa->value;
            $trace[] = ['rule' => 'final_waw_yaa', 'decision' => 'final_madd_wasl_previous_letter_rawi'];
        } else {
            $noonContext = $this->eligibility->resolveFinalNoon($word, $chars, $last);

            if (($noonContext['applies'] ?? false) === true) {
                $rawiIndex = (int) ($noonContext['rawi_index'] ?? $last);
                $rawi = $rawiIndex >= 0 && isset($chars[$rawiIndex])
                    ? ArabicText::normalizeForRawi($chars[$rawiIndex])
                    : null;
                $wasl = is_string($noonContext['wasl'] ?? null) ? $noonContext['wasl'] : null;
                $category = is_string($noonContext['category'] ?? null) ? $noonContext['category'] : QafyaCategory::Mutlaqa->value;
                $trace[] = [
                    'rule' => $noonContext['rule'] ?? 'extraneous_noon',
                    'decision' => $noonContext['decision'] ?? 'previous_letter_rawi_final_noon_wasl',
                    'reason' => $noonContext['reason'] ?? 'extraneous_noon_wasl',
                ];
                $ambiguities[] = [
                    'type' => 'extraneous_noon_resolved_by_context',
                    'letter' => 'ن',
                    'reason' => $noonContext['reason'] ?? 'extraneous_noon_wasl',
                ];
            } else {
                $rawiIndex = $last;
                $rawi = ArabicText::normalizeForRawi($final);
                $category = QafyaCategory::Muqayyada->value;
                $trace[] = ['rule' => 'final_consonant', 'decision' => 'final_letter_rawi'];
            }
        }

        if ($rawiIndex < 0 || $rawi === null || $rawi === '') {
            return $this->empty($word, $normalized, [
                ['type' => 'missing_rawi', 'message' => 'Could not resolve a rawi letter.'],
            ]);
        }

        $rawiSurface = $forcedRawiSurface ?? $rawi;
        $rawi = ArabicText::canonicalRawiIdentity($rawi);

        if ($rawi === null || $rawi === '') {
            return $this->empty($word, $normalized, [
                ['type' => 'missing_rawi', 'message' => 'Could not resolve a rawi letter.'],
            ]);
        }

        $radf = null;
        $taasis = null;
        $dakhiil = null;
        $beforeRawi = $chars[$rawiIndex - 1] ?? null;
        $twoBeforeRawi = $chars[$rawiIndex - 2] ?? null;
        if ($forcedRadf !== null) {
            $radf = $forcedRadf;
        } elseif ($this->isMaddOrAlefMadda($beforeRawi)) {
            $radf = $this->canonicalMaddLike($beforeRawi);
        } elseif (! $suppressTaasis && in_array($twoBeforeRawi, ['ا', 'ى'], true) && $beforeRawi !== null) {
            $taasis = 'ا';
            $dakhiil = ArabicText::normalizeForRawi($beforeRawi);
        }

        if ($surfacePattern === null) {
            $surfacePattern = $this->finalAlefMaddaSurfacePattern($chars, $rawiIndex);
        }

        if ($surfacePattern === null && ArabicText::isHamzaSeat($rawiSurface)) {
            $surfacePattern = $this->hamzaSeatSurfacePattern($chars, $rawiIndex);
        }

        $rawiHaraka = $this->harakaLocator->lastForLetter($word, $rawiSurface);
        $waslHaraka = $this->harakaLocator->lastForLetter($word, $wasl);
        $motions = $this->motions($rawiHaraka, $waslHaraka, $radf, $taasis, $dakhiil, $wasl, $khurooj);
        $rawiEligibility = $this->eligibility->assess($rawi);

        $components = [
            'taasis' => $this->component($taasis, ['role' => 'taasis']),
            'dakhiil' => $this->component($dakhiil, ['role' => 'dakhiil']),
            'radf' => $this->component($radf, ['role' => 'radf', 'family' => $this->radfFamily($radf)]),
            'rawi' => $this->component($rawi, [
                'role' => 'rawi',
                'surface_letter' => $rawiSurface !== $rawi ? $rawiSurface : null,
                'haraka' => $rawiHaraka['mark'],
                'haraka_name' => $rawiHaraka['name'],
                'mujra' => $motions['mujra'],
                'eligible' => $rawiEligibility['eligible'],
                'eligibility_reason' => $rawiEligibility['reason'],
                'eligibility_label' => $rawiEligibility['reason_label'],
                'eligibility_description' => $rawiEligibility['reason_description'],
            ]),
            'wasl' => $this->component($wasl, ['role' => 'wasl', 'haraka' => $waslHaraka['mark'], 'kind' => $wasl !== null && ArabicLetters::isHaa($wasl) ? 'haa' : 'madd']),
            'khurooj' => $this->component($khurooj, ['role' => 'khurooj']),
        ];

        $score = 0.75;
        if ($rawiEligibility['eligible']) {
            $score += 0.10;
        }
        if ($rawiHaraka['mark'] !== null) {
            $score += 0.08;
        }
        if ($warnings === [] && $ambiguities === []) {
            $score += 0.07;
        }
        $score = min(1.0, round($score, 4));

        return [
            'word' => $word,
            'normalized_word' => $normalized,
            'category' => $category,
            'components' => $components,
            'motions' => $motions,
            'surface_pattern' => $surfacePattern,
            'confidence' => ['score' => $score, 'level' => $this->level($score)],
            'trace' => $trace,
            'warnings' => $warnings,
            'ambiguities' => $ambiguities,
        ];
    }

    /**
     * Resolve bare final ه/ة without blindly promoting it to rawi.
     *
     * @param  list<string>  $chars
     * @return array{rawi_index: int, wasl: ?string, category: string, rule: string, decision: string, forced_radf?: string|null, suppress_taasis?: bool, ambiguous?: bool}
     */
    private function resolveBareHaaContext(string $originalWord, array $chars, int $last): array
    {
        $prev = $chars[$last - 1] ?? null;
        $prev2 = $chars[$last - 2] ?? null;

        $strippedOriginal = ArabicText::stripTashkeel($originalWord);
        $finalOriginalIsTaMarbuta = str_ends_with($strippedOriginal, 'ة');

        $finalHaaHaraka = $this->harakaLocator->lastForLetter($originalWord, 'ه');

        $prevHaraka = $prev !== null
            ? $this->harakaLocator->lastForLetter($originalWord, ArabicText::normalizeForRawi($prev))
            : ['mark' => null, 'name' => null, 'class' => null];

        $finalHaaHasExplicitMark = $finalHaaHaraka['mark'] !== null;
        $finalHaaIsSakin = ($finalHaaHaraka['class'] ?? null) === 'sukun';
        $prevLooksMoving = $prevHaraka['mark'] !== null && $prevHaraka['class'] !== 'sukun';

        /*
         * آه / وآه / شفاه / رآه:
         *
         * Here the final ه is the rawi and the alef-like letter before it is radf.
         * Vocalized ة is handled earlier as ت rawi. If unvocalized/paused ـاة
         * reaches this branch, it is treated as ه rawi with ا radf.
         */
        if (in_array($prev, ['ا', 'آ', 'ى'], true)) {
            return [
                'rawi_index' => $last,
                'wasl' => null,
                'forced_radf' => 'ا',
                'category' => QafyaCategory::Muqayyada->value,
                'rule' => 'final_haa_or_paused_taa_marbuta_after_alef_like',
                'decision' => 'haa_rawi_alef_like_radf_for_haa_or_paused_taa_marbuta',
            ];
        }

        /*
         * يدعوهُ / يدعوه:
         *
         * Haa after waw is often intended as the committed rawi in this family.
         * Keep this separate from ي + ه because عليه/إليه/أرتضيه are different.
         */
        if (! $finalOriginalIsTaMarbuta && $prev === 'و') {
            return [
                'rawi_index' => $last,
                'wasl' => null,
                'forced_radf' => 'و',
                'category' => QafyaCategory::Muqayyada->value,
                'rule' => 'final_haa_after_waw',
                'decision' => 'haa_rawi_waw_radf',
            ];
        }

        /*
         * الناحيهْ / العاريهْ:
         *
         * Final sakin haa after ي is a pause spelling. The visible qafya family is ي + ه,
         * so the rawi is ي and the final ه is wasl.
         *
         * Do not treat it like عليهِ / إليهِ, where the final haa is a vocalized pronoun.
         */
        if (! $finalOriginalIsTaMarbuta && $prev === 'ي' && $finalHaaIsSakin) {
            return [
                'rawi_index' => max(0, $last - 1),
                'wasl' => 'ه',
                'category' => QafyaCategory::Mutlaqa->value,
                'rule' => 'sakin_haa_after_yaa_pause',
                'decision' => 'yaa_rawi_final_haa_wasl',
                'suppress_taasis' => true,
            ];
        }

        /*
 * عليهِ / إليهِ / أرتضيهِ / سمتنيهِ:
 *
 * Final vocalized haa after ي is pronominal/wasl-like in these fixtures.
 * The rawi is before ي, and ي is radf.
 */
        if (! $finalOriginalIsTaMarbuta && $prev === 'ي' && $prev2 !== null && $finalHaaHasExplicitMark && ! $finalHaaIsSakin) {
            return [
                'rawi_index' => max(0, $last - 2),
                'wasl' => 'ه',
                'forced_radf' => 'ي',
                'category' => QafyaCategory::Mutlaqa->value,
                'rule' => 'vocalized_pronominal_haa_after_yaa',
                'decision' => 'pre_yaa_letter_rawi_yaa_radf_haa_wasl',
            ];
        }

        /*
         * سندسيّه / الناحيهْ / العاريهْ:
         *
         * Pause-form haa after ي should keep ي as the visible rawi family,
         * not promote ه to rawi.
         */
        if (
            $finalOriginalIsTaMarbuta
            || $prevLooksMoving
            || $finalHaaIsSakin
        ) {
            return [
                'rawi_index' => max(0, $last - 1),
                'wasl' => 'ه',
                'category' => QafyaCategory::Mutlaqa->value,
                'rule' => 'bare_haa_pause_or_ta_marbuta',
                'decision' => 'previous_letter_rawi_haa_wasl',
                'suppress_taasis' => $prev === 'ي',
            ];
        }

        /**
         * الدرايه / الوقايه / الحمايه / الرواية:
         *
         * In unvocalized modern spelling, final ـايه and ـاية belong to the same
         * qafya family:
         *   rawi = ي, radf = ا, wasl = ه
         *
         * Do not treat it like عليه / إليه, where final ه may be pronominal.
         *
         * Vocalized taa marbuta, such as الصلاةِ, is handled earlier as ت rawi.
         */
        if ($prev === 'ي' && in_array($prev2, ['ا', 'آ', 'ى'], true)) {
            return [
                'rawi_index' => max(0, $last - 1),
                'wasl' => 'ه',
                'forced_radf' => 'ا',
                'category' => QafyaCategory::Mutlaqa->value,
                'rule' => 'bare_aya_ayh_suffix',
                'decision' => 'yaa_rawi_alef_radf_final_haa_wasl',
                'suppress_taasis' => true,
            ];
        }

        /*
         * عليه / إليه without explicit final haraka:
         *
         * Keep the older safe reading: pre-yaa rawi + ي radf + ه wasl.
         */
        if ($prev === 'ي' && $prev2 !== null) {
            return [
                'rawi_index' => max(0, $last - 2),
                'wasl' => 'ه',
                'forced_radf' => 'ي',
                'category' => QafyaCategory::Mutlaqa->value,
                'rule' => 'bare_pronominal_haa_after_yaa',
                'decision' => 'pre_yaa_letter_rawi_yaa_radf_haa_wasl',
                'ambiguous' => true,
            ];
        }

        return [
            'rawi_index' => max(0, $last - 1),
            'wasl' => 'ه',
            'category' => QafyaCategory::Mutlaqa->value,
            'rule' => 'bare_haa',
            'decision' => 'previous_letter_rawi_haa_wasl',
            'ambiguous' => true,
        ];
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>|null
     */
    private function component(?string $letter, array $extra = []): ?array
    {
        if ($letter === null || $letter === '') {
            return null;
        }

        $role = is_string($extra['role'] ?? null) ? $extra['role'] : null;
        $component = $role === null ? null : QafyaComponent::tryFrom($role);

        return ['letter' => $letter]
            + $extra
            + [
                'label' => $component?->label(),
                'description' => $component?->description(),
            ];
    }

    private function radfFamily(?string $letter): ?string
    {
        return match ($letter) {
            'ا', 'ى' => 'alif',
            'و', 'ي' => 'waw_yaa',
            default => null,
        };
    }

    /**
     * @param  array{mark: ?string, name: ?string, class: ?string}  $rawiHaraka
     * @param  array{mark: ?string, name: ?string, class: ?string}  $waslHaraka
     * @return array<string, mixed>
     */
    private function motions(array $rawiHaraka, array $waslHaraka, ?string $radf, ?string $taasis, ?string $dakhiil, ?string $wasl, ?string $khurooj): array
    {
        return [
            'rass' => $taasis !== null ? 'required_before_taasis' : null,
            'ishbaa' => $dakhiil !== null ? $waslHaraka['class'] : null,
            'hadhw' => $radf !== null ? 'vowel_before_radf' : null,
            'tawjih' => $wasl === null ? $rawiHaraka['class'] : null,
            'mujra' => $rawiHaraka['class'],
            'nafadh' => $khurooj !== null ? 'vowel_before_khurooj' : null,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $warnings
     * @return array<string, mixed>
     */
    private function empty(string $word, string $normalized, array $warnings): array
    {
        return [
            'word' => $word,
            'normalized_word' => $normalized,
            'category' => QafyaCategory::Unknown->value,
            'components' => ['taasis' => null, 'dakhiil' => null, 'radf' => null, 'rawi' => null, 'wasl' => null, 'khurooj' => null],
            'motions' => ['rass' => null, 'ishbaa' => null, 'hadhw' => null, 'tawjih' => null, 'mujra' => null, 'nafadh' => null],
            'surface_pattern' => null,
            'confidence' => ['score' => 0.0, 'level' => 'none'],
            'trace' => [],
            'warnings' => $warnings,
            'ambiguities' => [],
        ];
    }

    private function level(float $score): string
    {
        return match (true) {
            $score >= 0.9 => 'high',
            $score >= 0.7 => 'medium',
            $score > 0.0 => 'low',
            default => 'none',
        };
    }

    private function isFinalAlefLike(?string $letter): bool
    {
        return in_array($letter, ['ا', 'آ'], true);
    }

    private function isFinalMaddLike(?string $letter): bool
    {
        return in_array($letter, ['ا', 'و', 'ي', 'ى', 'آ'], true);
    }

    private function isMaddOrAlefMadda(?string $letter): bool
    {
        return $letter !== null && (ArabicLetters::isMadd($letter) || $letter === 'آ');
    }

    private function canonicalMaddLike(?string $letter): ?string
    {
        return match ($letter) {
            'آ', 'ا', 'ى' => 'ا',
            'و' => 'و',
            'ي' => 'ي',
            default => null,
        };
    }

    private function hasExplicitMovingTaaMarbuta(string $word): bool
    {
        /*
         * ةِ / ةُ / ةَ / ةً / ةٌ / ةٍ
         * These are pronounced as ت in qafya, not pause ه.
         */
        return preg_match('/ة(?:ّ)?[ًٌٍَُِ]$/u', $word) === 1;
    }

    /**
     * @param  list<string>  $chars
     */
    private function finalAlefMaddaSurfacePattern(array $chars, int $rawiIndex): ?string
    {
        $last = count($chars) - 1;

        if (($chars[$last] ?? null) !== 'آ') {
            return null;
        }

        $start = max(0, $rawiIndex - 1);
        $surface = implode('', array_slice($chars, $start));

        return $surface !== '' ? $surface : null;
    }

    /**
     * Preserve the written hamza-seat surface while using ء as canonical rawi.
     *
     * Examples:
     * - مالئا  => ئا
     * - هادئا  => ئا
     * - عزائى  => ائى
     * - أحشائي => ائي
     *
     * @param  list<string>  $chars
     */
    private function hamzaSeatSurfacePattern(array $chars, int $rawiIndex): ?string
    {
        if (! isset($chars[$rawiIndex]) || ! ArabicText::isHamzaSeat($chars[$rawiIndex])) {
            return null;
        }

        $beforeRawi = $chars[$rawiIndex - 1] ?? null;

        $start = in_array($beforeRawi, ['ا', 'آ', 'ى'], true)
            ? $rawiIndex - 1
            : $rawiIndex;

        $surface = implode('', array_slice($chars, max(0, $start)));

        return $surface !== '' ? $surface : null;
    }
}
