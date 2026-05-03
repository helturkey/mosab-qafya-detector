<?php

declare(strict_types=1);

namespace Mosab\QafyaDetector\Enums;

enum QafyaSubtype: string
{
    case MutlaqaMuassasaHaa = 'mutlaqa_muassasa_connected_with_haa';
    case MutlaqaMuassasaMadd = 'mutlaqa_muassasa_connected_with_madd';
    case MutlaqaMardoofaHaa = 'mutlaqa_mardoofa_connected_with_haa';
    case MutlaqaMardoofaMadd = 'mutlaqa_mardoofa_connected_with_madd';
    case MutlaqaMardoofaLiin = 'mutlaqa_mardoofa_connected_with_liin';
    case MutlaqaMujarrada = 'mutlaqa_mujarrada';
    case MuqayyadaMuassasa = 'muqayyada_muassasa';
    case MuqayyadaMardoofa = 'muqayyada_mardoofa';
    case MuqayyadaMujarrada = 'muqayyada_mujarrada';
    case Unknown = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::MutlaqaMuassasaHaa => 'مطلقة مؤسسة موصولة بهاء',
            self::MutlaqaMuassasaMadd => 'مطلقة مؤسسة موصولة بمد',
            self::MutlaqaMardoofaHaa => 'مطلقة مردوفة موصولة بهاء',
            self::MutlaqaMardoofaMadd => 'مطلقة مردوفة موصولة بمد',
            self::MutlaqaMardoofaLiin => 'مطلقة مردوفة موصولة بلين',
            self::MutlaqaMujarrada => 'مطلقة مجردة',
            self::MuqayyadaMuassasa => 'مقيّدة مؤسسة',
            self::MuqayyadaMardoofa => 'مقيّدة مردوفة',
            self::MuqayyadaMujarrada => 'مقيّدة مجردة',
            self::Unknown => 'غير معروف',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::MutlaqaMuassasaHaa => 'قافية مطلقة فيها تأسيس ودخيل ووصل بهاء.',
            self::MutlaqaMuassasaMadd => 'قافية مطلقة فيها تأسيس ودخيل ووصل بحرف مد.',
            self::MutlaqaMardoofaHaa => 'قافية مطلقة فيها ردف قبل الروي ووصل بهاء.',
            self::MutlaqaMardoofaMadd => 'قافية مطلقة فيها ردف قبل الروي ووصل بحرف مد.',
            self::MutlaqaMardoofaLiin => 'قافية مطلقة فيها ردف لين مع وصل تابع.',
            self::MutlaqaMujarrada => 'قافية مطلقة لا تأسيس فيها ولا ردف.',
            self::MuqayyadaMuassasa => 'قافية مقيّدة فيها تأسيس ودخيل.',
            self::MuqayyadaMardoofa => 'قافية مقيّدة فيها ردف قبل الروي.',
            self::MuqayyadaMujarrada => 'قافية مقيّدة مجردة من التأسيس والردف.',
            self::Unknown => 'لم تتوفر قرائن كافية لتحديد subtype.',
        };
    }
}
