<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Doctrine\Translation;

use App\Shared\Domain\LocaleEnum;
use Doctrine\Common\Collections\Collection;

/**
 * @template TTranslation of TranslationInterface
 */
interface TranslatableInterface
{
    /**
     * @return Collection<int, TTranslation>
     */
    public function getTranslations(): Collection;

    /**
     * @param TTranslation $translation
     */
    public function addTranslation(TranslationInterface $translation): void;

    /**
     * @param TTranslation $translation
     */
    public function removeTranslation(TranslationInterface $translation): void;

    /**
     * @return null|TTranslation
     */
    public function getTranslation(LocaleEnum $locale): ?TranslationInterface;
}
