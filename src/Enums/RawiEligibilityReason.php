<?php

declare(strict_types=1);

namespace Mosab\QafyaDetector\Enums;

enum RawiEligibilityReason: string
{
    case MissingRawi = 'missing_rawi';
    case LetterDetectedAsWasl = 'letter_detected_as_wasl';
    case AcceptedConsonantalRawi = 'accepted_consonantal_rawi';
    case HaaRawiByMaddContext = 'haa_rawi_by_madd_context';
    case FinalMaddWasl = 'final_madd_or_liin_wasl';
    case FinalMaddNeedsPoemContext = 'madd_or_liin_needs_poem_context';
    case FinalAlefMaqsuraNeedsPoemContext = 'final_alef_maqsura_needs_poem_context';
    case PronominalHaaWasl = 'pronominal_haa_wasl';
    case TaMarbutaPauseWasl = 'ta_marbuta_pause_wasl';
    case ExtraneousNoonWasl = 'extraneous_noon_wasl';
    case PossibleExtraneousNoon = 'possible_extraneous_noon_needs_context';
    case PoemLevelOverride = 'poem_level_override';

    public function label(): string
    {
        return match ($this) {
            self::MissingRawi => 'روي مفقود',
            self::LetterDetectedAsWasl => 'حرف وصل',
            self::AcceptedConsonantalRawi => 'روي صحيح مقبول',
            self::HaaRawiByMaddContext => 'هاء روي بسياق المد',
            self::FinalMaddWasl => 'مد نهائي وصل',
            self::FinalMaddNeedsPoemContext => 'مد أو لين يحتاج سياق القصيدة',
            self::FinalAlefMaqsuraNeedsPoemContext => 'ألف مقصورة تحتاج سياق القصيدة',
            self::PronominalHaaWasl => 'هاء ضمير وصل',
            self::TaMarbutaPauseWasl => 'هاء تأنيث/وقف وصل',
            self::ExtraneousNoonWasl => 'نون زائدة وصل',
            self::PossibleExtraneousNoon => 'نون محتملة الزيادة',
            self::PoemLevelOverride => 'حسم على مستوى القصيدة',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::MissingRawi => 'لم يتمكن المحلل من تحديد روي صالح.',
            self::LetterDetectedAsWasl => 'الحرف تابع للروي ولا يبنى عليه الحكم.',
            self::AcceptedConsonantalRawi => 'حرف صحيح أو مقبول في موضع الروي.',
            self::HaaRawiByMaddContext => 'الهاء روي لأنها ثابتة بعد ردف مدّي مثل ينساه/يدعوه.',
            self::FinalMaddWasl => 'الألف أو الواو أو الياء النهائية تعامل كوصل زائد.',
            self::FinalMaddNeedsPoemContext => 'حرف العلة قد يكون رويًا أو وصلًا حسب انتظام القصيدة.',
            self::FinalAlefMaqsuraNeedsPoemContext => 'الألف المقصورة تحفظ كتابيًا وتحتاج سياقًا لتأكيد دورها.',
            self::PronominalHaaWasl => 'الهاء ضمير غالبًا، فتتبع ما قبلها.',
            self::TaMarbutaPauseWasl => 'التاء المربوطة تقف هاءً ولا تكون رويًا غالبًا.',
            self::ExtraneousNoonWasl => 'نون توكيد أو نسوة أو ترنم ظاهرة لا تصلح رويًا.',
            self::PossibleExtraneousNoon => 'النون قد تكون أصلية أو زائدة ولا يكفي سياق الكلمة وحده.',
            self::PoemLevelOverride => 'سياق القصيدة أعاد تفسير دور الحرف.',
        };
    }
}
