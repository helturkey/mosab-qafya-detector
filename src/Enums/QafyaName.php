<?php

declare(strict_types=1);

namespace Mosab\QafyaDetector\Enums;

enum QafyaName: string
{
    case Mutaradif = 'mutaradif';
    case Mutawatir = 'mutawatir';
    case Mutadarik = 'mutadarik';
    case Mutarakib = 'mutarakib';
    case Mutakawis = 'mutakawis';

    public static function fromMovingCount(int $movingCount): ?self
    {
        return match ($movingCount) {
            0 => self::Mutaradif,
            1 => self::Mutawatir,
            2 => self::Mutadarik,
            3 => self::Mutarakib,
            4 => self::Mutakawis,
            default => null,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Mutaradif => 'المترادف',
            self::Mutawatir => 'المتواتر',
            self::Mutadarik => 'المتدارك',
            self::Mutarakib => 'المتراكب',
            self::Mutakawis => 'المتكاوس',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Mutaradif => 'لا متحرك بين ساكني القافية.',
            self::Mutawatir => 'متحرك واحد بين ساكني القافية.',
            self::Mutadarik => 'متحركان بين ساكني القافية.',
            self::Mutarakib => 'ثلاثة متحركات بين ساكني القافية.',
            self::Mutakawis => 'أربعة متحركات بين ساكني القافية.',
        };
    }
}
