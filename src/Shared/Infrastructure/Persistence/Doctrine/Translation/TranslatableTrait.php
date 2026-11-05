<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Doctrine\Translation;

use App\Shared\Domain\LocaleEnum;
use App\Shared\Infrastructure\Persistence\Doctrine\Validation\UniqueTranslationLocales;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @template TTranslation of TranslationInterface
 */
trait TranslatableTrait
{
    /**
     * @var Collection<int, TTranslation>
     */
    #[ORM\OneToMany(
        targetEntity: TranslationInterface::class,
        mappedBy: 'translatable',
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    #[Assert\Count(min: 1, minMessage: 'translatable.translations.min_count')]
    #[UniqueTranslationLocales]
    #[Assert\Valid]
    private Collection $translations;

    /**
     * @return Collection<int, TTranslation>
     */
    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    /**
     * @param TTranslation $translation
     */
    public function addTranslation(TranslationInterface $translation): void
    {
        if (!$this->translations->contains($translation)) {
            $this->translations->add($translation);
            $translation->setTranslatable($this);
        }
    }

    /**
     * @param TTranslation $translation
     */
    public function removeTranslation(TranslationInterface $translation): void
    {
        $this->translations->removeElement($translation);
    }

    public function getTranslation(LocaleEnum $locale): ?TranslationInterface
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('locale', $locale))
            ->setMaxResults(1)
        ;

        $translation = $this->translations->matching($criteria)->first() ?: null;

        if ($translation instanceof TranslationInterface) {
            return $translation;
        }

        return null;
    }
}
