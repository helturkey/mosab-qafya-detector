<?php

declare(strict_types=1);

use Mosab\QafyaDetector\Enums\QafyaCategory;
use Mosab\QafyaDetector\Enums\QafyaComponent;
use Mosab\QafyaDetector\Enums\QafyaDefect;
use Mosab\QafyaDetector\Enums\QafyaMotion;
use Mosab\QafyaDetector\Enums\QafyaName;
use Mosab\QafyaDetector\Enums\QafyaStatus;
use Mosab\QafyaDetector\Enums\QafyaSubtype;
use Mosab\QafyaDetector\Enums\RawiEligibilityReason;
use Mosab\QafyaDetector\Enums\SanadType;

it('exposes Arabic labels and short descriptions for public enums', function (): void {
    $cases = [
        QafyaCategory::Mutlaqa,
        QafyaName::Mutadarik,
        QafyaSubtype::MuqayyadaMardoofa,
        QafyaComponent::Rawi,
        QafyaMotion::Mujra,
        QafyaStatus::Review,
        QafyaDefect::Iqwa,
        SanadType::Radf,
        RawiEligibilityReason::FinalMaddWasl,
    ];

    foreach ($cases as $case) {
        expect($case->label())->not->toBe('')
            ->and($case->description())->not->toBe('');
    }
});
