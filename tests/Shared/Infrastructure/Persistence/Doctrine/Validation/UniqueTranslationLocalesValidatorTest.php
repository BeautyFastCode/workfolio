<?php

declare(strict_types=1);

namespace App\Tests\Shared\Infrastructure\Persistence\Doctrine\Validation;

use App\Content\Shared\Infrastructure\Persistence\Doctrine\Entity\PageTranslation;
use App\Shared\Domain\LocaleEnum;
use App\Shared\Infrastructure\Persistence\Doctrine\Validation\UniqueTranslationLocales;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

/**
 * @internal
 *
 * @covers \App\Shared\Infrastructure\Persistence\Doctrine\Validation\UniqueTranslationLocales
 * @covers \App\Shared\Infrastructure\Persistence\Doctrine\Validation\UniqueTranslationLocalesValidator
 */
final class UniqueTranslationLocalesValidatorTest extends TestCase
{
    public function test_it_reports_each_duplicate_locale_at_the_duplicate_item_path(): void
    {
        $english = new PageTranslation();
        $english->setLocale(LocaleEnum::EN);
        $duplicateEnglish = new PageTranslation();
        $duplicateEnglish->setLocale(LocaleEnum::EN);

        $violations = Validation::createValidator()->validate(
            [$english, $duplicateEnglish],
            new UniqueTranslationLocales(),
        );

        self::assertCount(1, $violations);
        $violation = $violations->get(0);
        self::assertSame('[1].locale', $violation->getPropertyPath());
        self::assertSame('translatable.translations.unique', $violation->getMessageTemplate());
        self::assertSame(['{{ locale }}' => 'EN'], $violation->getParameters());
    }

    public function test_it_accepts_distinct_locales_and_ignores_non_collections(): void
    {
        $english = new PageTranslation();
        $english->setLocale(LocaleEnum::EN);
        $polish = new PageTranslation();
        $polish->setLocale(LocaleEnum::PL);
        $validator = Validation::createValidator();
        $constraint = new UniqueTranslationLocales();

        self::assertCount(0, $validator->validate([$english, $polish], $constraint));
        self::assertCount(0, $validator->validate(null, $constraint));
        self::assertCount(0, $validator->validate('not-a-collection', $constraint));
    }
}
