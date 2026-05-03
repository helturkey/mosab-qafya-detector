<?php

declare(strict_types=1);

namespace Mosab\QafyaDetector\Classification;

use Mosab\QafyaDetector\Enums\QafyaName;

/**
 * Classifies qafya name by the number of moving letters between the last two sakins.
 */
final class QafyaNameClassifier
{
    public function classify(?int $movingCount): ?string
    {
        return $movingCount === null ? null : QafyaName::fromMovingCount($movingCount)?->value;
    }

    public function arabicName(?string $name): ?string
    {
        return $name === null ? null : QafyaName::tryFrom($name)?->label();
    }

    public function description(?string $name): ?string
    {
        return $name === null ? null : QafyaName::tryFrom($name)?->description();
    }
}
