<?php

declare(strict_types=1);

namespace Mosab\QafyaDetector\Analysis\Rules;

/** Promotes final ي to rawi when poem context proves it is stable. */
final class FinalYaaRule extends AbstractPoemLevelRule
{
    /**
     * @param  list<array<string, mixed>>  $endings
     * @return list<array<string, mixed>>
     */
    public function apply(array $endings): array
    {
        return $this->applyPoemLevelFinalYaaOverride($endings);
    }

    /**
     * Promote final ي to rawi at poem level when most endings share final ي,
     * while the letter before it varies.
     *
     * This covers qafya families such as:
     * - البهيِّ / السميِّ / العليِّ / الشهيِّ
     * - إليّ / طيّ / فيّ / شيّ / حيّ
     *
     * Word-level detection may wrongly read final ي as wasl or khurooj in
     * isolation. Poem-level consistency proves that the final ي itself is
     * the committed rawi.
     *
     * @param  list<array<string, mixed>>  $endings
     * @return list<array<string, mixed>>
     */
    private function applyPoemLevelFinalYaaOverride(array $endings): array
    {
        $valid = array_values(array_filter(
            $endings,
            static fn (array $ending): bool => ($ending['result']['status'] ?? null) === 'ok'
        ));

        if (count($valid) < 3) {
            return $endings;
        }

        $yaaEndingCount = 0;

        /** @var array<string, int> $preYaaMap */
        $preYaaMap = [];

        foreach ($valid as $ending) {
            $result = is_array($ending['result'] ?? null) ? $ending['result'] : [];
            $word = is_string($result['input']['normalized'] ?? null)
                ? $result['input']['normalized']
                : '';

            if (! $this->endsWithYaa($word)) {
                continue;
            }

            $preYaa = $this->letterBeforeFinalYaa($word);

            if ($preYaa === null) {
                continue;
            }

            /*
             * Do not rely on word-level wasl === ي here.
             *
             * In endings like البهيِّ, word-level may produce:
             * rawi=ب, wasl=ه, khurooj=ي
             *
             * That is exactly the false analysis this poem-level override
             * is supposed to repair.
             */
            $yaaEndingCount++;
            $preYaaMap[$preYaa] = ($preYaaMap[$preYaa] ?? 0) + 1;
        }

        $ratio = $yaaEndingCount / max(1, count($valid));

        /*
         * Stable pre-yā letter means final ي is probably wasl:
         * مكاسبي / مثالبي / تجاربي => rawi=ب, wasl=ي
         *
         * Diverse pre-yā letters mean final ي is the poem-level rawi:
         * البهي / السمي / العلي / الشهي => rawi=ي
         */
        if ($ratio < 0.8 || count($preYaaMap) < 2) {
            return $endings;
        }

        foreach ($endings as &$ending) {
            if (($ending['result']['status'] ?? null) !== 'ok') {
                continue;
            }

            $result = is_array($ending['result'] ?? null) ? $ending['result'] : [];
            $word = is_string($result['input']['normalized'] ?? null)
                ? $result['input']['normalized']
                : '';

            if (! $this->endsWithYaa($word)) {
                continue;
            }

            $ending['result'] = $this->promoteFinalYaaToRawi($result);
        }

        unset($ending);

        return $endings;
    }

    private function letterBeforeFinalYaa(string $word): ?string
    {
        if (! $this->endsWithYaa($word)) {
            return null;
        }

        $chars = mb_str_split($word) ?: [];

        if (count($chars) < 2) {
            return null;
        }

        return $chars[count($chars) - 2];
    }

    private function endsWithYaa(string $word): bool
    {
        return $word !== '' && str_ends_with($word, 'ي');
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function promoteFinalYaaToRawi(array $result): array
    {
        $result['qafya']['category'] = 'muqayyada';
        $result['qafya']['subtype'] = 'muqayyada_mujarrada';

        $result['qafya']['components']['taasis'] = null;
        $result['qafya']['components']['dakhiil'] = null;
        $result['qafya']['components']['radf'] = null;

        $result['qafya']['components']['rawi'] = [
            'letter' => 'ي',
            'role' => 'rawi',
            'haraka' => null,
            'haraka_name' => null,
            'mujra' => null,
            'eligible' => true,
            'eligibility_reason' => 'poem_level_final_yaa_rawi',
        ];

        $result['qafya']['components']['wasl'] = null;
        $result['qafya']['components']['khurooj'] = null;

        $result['qafya']['motions']['mujra'] = null;
        $result['qafya']['motions']['tawjih'] = null;
        $result['qafya']['motions']['hadhw'] = null;
        $result['qafya']['motions']['ishbaa'] = null;
        $result['qafya']['motions']['nafadh'] = null;

        $components = is_array($result['qafya']['components'] ?? null)
            ? $result['qafya']['components']
            : [];

        $motions = is_array($result['qafya']['motions'] ?? null)
            ? $result['qafya']['motions']
            : [];

        $signatures = $this->signatureBuilder->build($components, $motions);

        $surface = 'ي';

        $result['signatures'] = $signatures;
        $result['signatures']['visual'] = $surface;

        $result['pattern'] = [
            'surface' => $surface,
            'component' => $signatures['component'],
            'strict' => $signatures['strict'],
            'cluster' => $signatures['cluster'],
            'canonical' => $signatures['component'],
        ];

        $result['segment'] = [
            'surface' => $surface,
            'arudic' => $surface,
            'method' => 'poem_level_final_yaa_rawi',
            'complete' => true,
            'moving_count_between_sakins' => null,
            'last_sakin_index' => null,
            'previous_sakin_index' => null,
            'start_index' => null,
            'warnings' => [],
        ];

        $result['qafya']['pattern'] = $result['pattern'];
        $result['qafya']['segment'] = $result['segment'];
        $result['qafya']['boundary'] = $result['segment'];

        if (isset($result['trace']) && is_array($result['trace'])) {
            $result['trace'][] = [
                'rule' => 'poem_level_final_yaa_override',
                'decision' => 'final_yaa_promoted_to_rawi_because_pre_yaa_letters_vary',
            ];
        }

        return $result;
    }
}
