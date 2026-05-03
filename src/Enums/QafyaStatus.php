<?php

declare(strict_types=1);

namespace Mosab\QafyaDetector\Enums;

enum QafyaStatus: string
{
    case Ok = 'ok';
    case Review = 'review';
    case Partial = 'partial';
    case Error = 'error';

    public function label(): string
    {
        return match ($this) {
            self::Ok => 'سليم',
            self::Review => 'يحتاج مراجعة',
            self::Partial => 'تحليل جزئي',
            self::Error => 'خطأ',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Ok => 'اكتمل التحليل بلا عيوب ظاهرة.',
            self::Review => 'اكتمل التحليل مع قرائن تحتاج مراجعة.',
            self::Partial => 'لم تتوفر بيانات كافية لتحليل كامل.',
            self::Error => 'تعذر التحليل بسبب مدخل غير صالح.',
        };
    }
}
