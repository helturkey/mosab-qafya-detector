<?php

declare(strict_types=1);

namespace Mosab\QafyaDetector\Enums;

enum QafyaComponent: string
{
    case Taasis = 'taasis';
    case Dakhiil = 'dakhiil';
    case Radf = 'radf';
    case Rawi = 'rawi';
    case Wasl = 'wasl';
    case Khurooj = 'khurooj';

    public function label(): string
    {
        return match ($this) {
            self::Taasis => 'التأسيس',
            self::Dakhiil => 'الدخيل',
            self::Radf => 'الردف',
            self::Rawi => 'الروي',
            self::Wasl => 'الوصل',
            self::Khurooj => 'الخروج',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Taasis => 'ألف قبل الروي يفصل بينها وبين الروي حرف دخيل.',
            self::Dakhiil => 'الحرف الواقع بين ألف التأسيس والروي.',
            self::Radf => 'حرف مد أو لين قبل الروي مباشرة.',
            self::Rawi => 'حرف القافية الأساسي الذي تبنى عليه القصيدة.',
            self::Wasl => 'حرف يلحق الروي في القافية المطلقة.',
            self::Khurooj => 'مد زائد بعد هاء الوصل.',
        };
    }
}
