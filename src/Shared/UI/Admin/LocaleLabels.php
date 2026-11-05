<?php

declare(strict_types=1);

namespace App\Shared\UI\Admin;

use App\Shared\Domain\LocaleEnum;

final class LocaleLabels
{
    public static function translationKey(LocaleEnum $locale): string
    {
        return match ($locale) {
            LocaleEnum::PL => 'locale.pl',
            LocaleEnum::EN => 'locale.en',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function labelsWithFlagValues(): array
    {
        return [
            LocaleEnum::PL->value => '🇵🇱 Polski',
            LocaleEnum::EN->value => '🇬🇧 English',
        ];
    }
}
