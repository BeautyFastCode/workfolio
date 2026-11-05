<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Doctrine\Translation;

use App\Shared\Domain\LocaleEnum;

/**
 * @template TTranslatable of TranslatableInterface
 */
interface TranslationInterface
{
    public function getLocale(): LocaleEnum;

    public function setLocale(LocaleEnum $locale): void;

    /**
     * @return TTranslatable
     */
    public function getTranslatable(): TranslatableInterface;

    /**
     * @param TTranslatable $translatable
     */
    public function setTranslatable(TranslatableInterface $translatable): void;
}
