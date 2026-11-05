<?php

declare(strict_types=1);

namespace App\Tests\Shared\UI\Admin;

use App\Shared\Domain\LocaleEnum;
use App\Shared\UI\Admin\LocaleLabels;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(LocaleLabels::class)]
final class LocaleLabelsTest extends TestCase
{
    public function test_it_keeps_translation_keys_and_dashboard_labels_outside_the_locale_type(): void
    {
        self::assertSame('locale.pl', LocaleLabels::translationKey(LocaleEnum::PL));
        self::assertSame('locale.en', LocaleLabels::translationKey(LocaleEnum::EN));
        self::assertSame([
            'pl' => '🇵🇱 Polski',
            'en' => '🇬🇧 English',
        ], LocaleLabels::labelsWithFlagValues());
    }
}
