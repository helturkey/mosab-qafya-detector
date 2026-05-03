<?php

declare(strict_types=1);

namespace Mosab\QafyaDetector\Enums;

enum QafyaDefect: string
{
    case RawiMismatch = 'rawi_mismatch';
    case Iqwa = 'iqwa';
    case Israf = 'israf';
    case Ikfa = 'ikfa';
    case Ijaza = 'ijaza';
    case Ita = 'ita';
    case Tadmeen = 'tadmeen';

    public function label(): string
    {
        return match ($this) {
            self::RawiMismatch => 'اختلاف الروي',
            self::Iqwa => 'الإقواء',
            self::Israf => 'الإصراف',
            self::Ikfa => 'الإكفاء',
            self::Ijaza => 'الإجازة',
            self::Ita => 'الإيطاء',
            self::Tadmeen => 'التضمين',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::RawiMismatch => 'اختلاف حرف الروي بين الأبيات.',
            self::Iqwa => 'اختلاف حركة الروي مع اتحاد الحرف.',
            self::Israf => 'اختلاف بين حركة فتح وغيرها في الروي.',
            self::Ikfa => 'إبدال الروي بحرف مقارب في المخرج.',
            self::Ijaza => 'اختلاف الروي بحرف بعيد مع بقاء الوزن.',
            self::Ita => 'تكرار كلمة القافية نفسها بغير مسافة مقبولة.',
            self::Tadmeen => 'احتياج البيت التالي لاكتمال معنى السابق.',
        };
    }
}
