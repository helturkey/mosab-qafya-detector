<?php

declare(strict_types=1);

namespace Mosab\QafyaDetector\Enums;

enum QafyaMotion: string
{
    case Rass = 'rass';
    case Ishbaa = 'ishbaa';
    case Hadhw = 'hadhw';
    case Tawjih = 'tawjih';
    case Mujra = 'mujra';
    case Nafadh = 'nafadh';

    public function label(): string
    {
        return match ($this) {
            self::Rass => 'الرس',
            self::Ishbaa => 'الإشباع',
            self::Hadhw => 'الحذو',
            self::Tawjih => 'التوجيه',
            self::Mujra => 'المجرى',
            self::Nafadh => 'النفاذ',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Rass => 'حركة ما قبل ألف التأسيس.',
            self::Ishbaa => 'حركة الدخيل أو ما يتصل بالوصل.',
            self::Hadhw => 'حركة ما قبل الردف.',
            self::Tawjih => 'حركة ما قبل الروي المقيّد.',
            self::Mujra => 'حركة الروي المطلق.',
            self::Nafadh => 'حركة هاء الوصل قبل الخروج.',
        };
    }
}
