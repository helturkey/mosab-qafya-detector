<?php

declare(strict_types=1);

namespace Mosab\QafyaDetector\Enums;

enum SanadType: string
{
    case Radf = 'sanad_radf';
    case Taasis = 'sanad_taasis';
    case Ishbaa = 'sanad_ishbaa';
    case Hadhw = 'sanad_hadhw';
    case Tawjih = 'sanad_tawjih';

    public function label(): string
    {
        return match ($this) {
            self::Radf => 'سناد الردف',
            self::Taasis => 'سناد التأسيس',
            self::Ishbaa => 'سناد الإشباع',
            self::Hadhw => 'سناد الحذو',
            self::Tawjih => 'سناد التوجيه',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Radf => 'اختلاف الالتزام بالردف أو عائلته.',
            self::Taasis => 'اختلاف وجود ألف التأسيس.',
            self::Ishbaa => 'اختلاف حركة الدخيل أو ما يتصل بها.',
            self::Hadhw => 'اختلاف حركة ما قبل الردف.',
            self::Tawjih => 'اختلاف حركة ما قبل الروي المقيّد.',
        };
    }
}
