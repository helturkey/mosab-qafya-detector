<?php

declare(strict_types=1);

namespace Mosab\QafyaDetector\Classification;

use Mosab\QafyaDetector\Enums\QafyaCategory;
use Mosab\QafyaDetector\Enums\QafyaSubtype;

/**
 * Classifies mutlaqa/muqayyada subtype using the six qafya letters.
 */
final class QafyaSubtypeClassifier
{
    /** @param array<string, mixed> $components */
    public function classify(string $category, array $components): string
    {
        $hasTaasis = is_array($components['taasis'] ?? null) && ($components['taasis']['letter'] ?? null) !== null;
        $hasRadf = is_array($components['radf'] ?? null) && ($components['radf']['letter'] ?? null) !== null;
        $wasl = is_array($components['wasl'] ?? null) ? ($components['wasl']['letter'] ?? null) : null;
        $waslIsHaa = in_array($wasl, ['ه', 'ة'], true);
        $waslIsMadd = in_array($wasl, ['ا', 'و', 'ي', 'ى'], true);

        if ($category === QafyaCategory::Mutlaqa->value) {
            if ($hasTaasis && $waslIsHaa) {
                return QafyaSubtype::MutlaqaMuassasaHaa->value;
            }
            if ($hasTaasis && $waslIsMadd) {
                return QafyaSubtype::MutlaqaMuassasaMadd->value;
            }
            if ($hasRadf && $waslIsHaa) {
                return QafyaSubtype::MutlaqaMardoofaHaa->value;
            }
            if ($hasRadf && $waslIsMadd) {
                return QafyaSubtype::MutlaqaMardoofaMadd->value;
            }
            if ($hasRadf) {
                return QafyaSubtype::MutlaqaMardoofaLiin->value;
            }

            return QafyaSubtype::MutlaqaMujarrada->value;
        }

        if ($hasTaasis) {
            return QafyaSubtype::MuqayyadaMuassasa->value;
        }
        if ($hasRadf) {
            return QafyaSubtype::MuqayyadaMardoofa->value;
        }

        return QafyaSubtype::MuqayyadaMujarrada->value;
    }

    public function label(?string $subtype): ?string
    {
        return $subtype === null ? null : QafyaSubtype::tryFrom($subtype)?->label();
    }

    public function description(?string $subtype): ?string
    {
        return $subtype === null ? null : QafyaSubtype::tryFrom($subtype)?->description();
    }
}
