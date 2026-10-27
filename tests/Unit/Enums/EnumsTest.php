<?php

declare(strict_types=1);

use Mosab\QafyaDetector\Enums\QafyaCategory;
use Mosab\QafyaDetector\Enums\QafyaDefect;
use Mosab\QafyaDetector\Enums\QafyaName;
use Mosab\QafyaDetector\Enums\QafyaStatus;

it('maps the moving count between sakins to the qafya name', function (): void {
    expect(QafyaName::fromMovingCount(0))->toBe(QafyaName::Mutaradif)
        ->and(QafyaName::fromMovingCount(1))->toBe(QafyaName::Mutawatir)
        ->and(QafyaName::fromMovingCount(2))->toBe(QafyaName::Mutadarik)
        ->and(QafyaName::fromMovingCount(3))->toBe(QafyaName::Mutarakib)
        ->and(QafyaName::fromMovingCount(4))->toBe(QafyaName::Mutakawis)
        ->and(QafyaName::fromMovingCount(5))->toBeNull()
        ->and(QafyaName::fromMovingCount(-1))->toBeNull();
});

it('gives every qafya name an Arabic label and description', function (): void {
    foreach (QafyaName::cases() as $name) {
        expect($name->label())->toBeString()->not->toBe('')
            ->and($name->description())->toBeString()->not->toBe('');
    }

    expect(QafyaName::Mutaradif->label())->toBe('المترادف')
        ->and(QafyaName::Mutakawis->description())->toBe('أربعة متحركات بين ساكني القافية.');
});

it('gives every defect, category, and status a label and description', function (): void {
    foreach ([...QafyaDefect::cases(), ...QafyaCategory::cases(), ...QafyaStatus::cases()] as $case) {
        expect($case->label())->toBeString()->not->toBe('')
            ->and($case->description())->toBeString()->not->toBe('');
    }
});

it('backs the defects that the poem detector emits as defect enum cases', function (): void {
    foreach (['rawi_mismatch', 'iqwa', 'israf', 'ita'] as $type) {
        expect(QafyaDefect::tryFrom($type))->not->toBeNull($type);
    }

    expect(QafyaDefect::Israf->label())->toBe('الإصراف')
        ->and(QafyaDefect::Iqwa->label())->toBe('الإقواء')
        ->and(QafyaDefect::tryFrom('sanad_radf'))->toBeNull();
});

it('exposes the documented category and status values', function (): void {
    expect(array_map(static fn (QafyaCategory $case): string => $case->value, QafyaCategory::cases()))->toBe(['muqayyada', 'mutlaqa', 'mixed', 'unknown'])
        ->and(array_map(static fn (QafyaStatus $case): string => $case->value, QafyaStatus::cases()))->toBe(['ok', 'review', 'partial', 'error']);
});
