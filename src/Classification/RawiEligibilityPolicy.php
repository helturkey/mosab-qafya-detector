<?php

declare(strict_types=1);

namespace Mosab\QafyaDetector\Classification;

use Mosab\QafyaDetector\Enums\RawiEligibilityReason;

/**
 * Context-aware policy for whether a candidate letter is suitable as rawi.
 *
 * This is deliberately not a hard blacklist. Classical qafya allows some weak
 * letters and haa to be rawi in the right context, but the same letters are
 * often wasl/radf/khurooj when they are extra or pronominal.
 */
final class RawiEligibilityPolicy
{
    /**
     * @return array{eligible: bool, reason: string, reason_label: string, reason_description: string, may_be_wasl: bool}
     */
    public function assess(?string $letter, string $role = 'rawi'): array
    {
        if ($letter === null || $letter === '') {
            return $this->result(false, RawiEligibilityReason::MissingRawi, false);
        }

        if ($role === 'wasl') {
            return $this->result(false, RawiEligibilityReason::LetterDetectedAsWasl, true);
        }

        if ($letter === 'ى') {
            return $this->result(true, RawiEligibilityReason::FinalAlefMaqsuraNeedsPoemContext, true);
        }

        if (in_array($letter, ['ا', 'و', 'ي'], true)) {
            return $this->result(false, RawiEligibilityReason::FinalMaddNeedsPoemContext, true);
        }

        return $this->result(true, RawiEligibilityReason::AcceptedConsonantalRawi, false);
    }

    /**
     * Resolve a clear final extraneous nūn before generic final-consonant logic.
     *
     * @param  list<string>  $chars
     * @return array{
     *     applies: bool,
     *     rawi_index?: int,
     *     wasl?: string,
     *     category?: string,
     *     rule?: string,
     *     decision?: string,
     *     reason?: string,
     *     ambiguous?: bool
     * }
     */
    public function resolveFinalNoon(string $originalWord, array $chars, int $last): array
    {
        if (($chars[$last] ?? null) !== 'ن' || $last < 1) {
            return ['applies' => false];
        }

        if ($this->hasFinalNoonShadda($originalWord)) {
            return [
                'applies' => true,
                'rawi_index' => $last - 1,
                'wasl' => 'ن',
                'category' => 'mutlaqa',
                'rule' => 'extraneous_noon_tawkid',
                'decision' => 'previous_letter_rawi_final_noon_wasl',
                'reason' => RawiEligibilityReason::ExtraneousNoonWasl->value,
            ];
        }

        if ($this->looksLikeNoonNiswa($originalWord, $chars)) {
            return [
                'applies' => true,
                'rawi_index' => $last - 1,
                'wasl' => 'ن',
                'category' => 'mutlaqa',
                'rule' => 'extraneous_noon_niswa',
                'decision' => 'previous_letter_rawi_final_noon_wasl',
                'reason' => RawiEligibilityReason::ExtraneousNoonWasl->value,
            ];
        }

        return ['applies' => false];
    }

    /**
     * @return array{eligible: bool, reason: string, reason_label: string, reason_description: string, may_be_wasl: bool}
     */
    private function result(bool $eligible, RawiEligibilityReason $reason, bool $mayBeWasl): array
    {
        return [
            'eligible' => $eligible,
            'reason' => $reason->value,
            'reason_label' => $reason->label(),
            'reason_description' => $reason->description(),
            'may_be_wasl' => $mayBeWasl,
        ];
    }

    private function hasFinalNoonShadda(string $word): bool
    {
        return preg_match('/ن(?:[ًٌٍَُِْ])*ّ(?:[ًٌٍَُِْ])*$/u', $word) === 1
            || preg_match('/ن(?:[ًٌٍَُِْ])*ّ(?:[ًٌٍَُِْ])*[اى]?$/u', $word) === 1;
    }

    /**
     * @param  list<string>  $chars
     */
    private function looksLikeNoonNiswa(string $word, array $chars): bool
    {
        $normalized = implode('', $chars);

        if (count($chars) < 4 || ! str_ends_with($normalized, 'ن')) {
            return false;
        }

        /*
         * Keep nūn al-niswa very conservative.
         *
         * Without a visible final fatḥa, forms like:
         *   جانحانْ / مهرجانْ / المكانْ / الزمانْ
         * can be misread as suffixal nūn.
         *
         * Unvocalized final ن should remain a normal rawi by default.
         */
        if (preg_match('/^[يتأ][\x{0621}-\x{063A}\x{0641}-\x{064A}]{2,}ن$/u', $normalized) !== 1) {
            return false;
        }

        return preg_match('/نَ$/u', $word) === 1;
    }
}
