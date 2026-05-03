<?php

declare(strict_types=1);

namespace Mosab\QafyaDetector\Enums;

enum QafyaCategory: string
{
    case Muqayyada = 'muqayyada';
    case Mutlaqa = 'mutlaqa';
    case Mixed = 'mixed';
    case Unknown = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::Muqayyada => 'مقيّدة',
            self::Mutlaqa => 'مطلقة',
            self::Mixed => 'مختلطة',
            self::Unknown => 'غير معروفة',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Muqayyada => 'قافية ينتهي رويّها بالسكون ولا يلحقه وصل.',
            self::Mutlaqa => 'قافية يلحق رويّها وصل أو امتداد صوتي.',
            self::Mixed => 'تحليل قصيدة تتضمن أكثر من قراءة قافية.',
            self::Unknown => 'لم تتوفر قرائن كافية لتصنيف نوع القافية.',
        };
    }
}
