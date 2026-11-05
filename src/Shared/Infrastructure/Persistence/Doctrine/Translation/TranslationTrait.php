<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Doctrine\Translation;

use App\Shared\Domain\LocaleEnum;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @template TTranslatable of TranslatableInterface
 */
#[ORM\UniqueConstraint(
    name: 'uniq_translatable_translation',
    columns: ['translatable_id', 'locale']
)]
trait TranslationTrait
{
    #[ORM\Column(name: 'locale', type: Types::ENUM, length: 5, enumType: LocaleEnum::class)]
    #[Assert\NotNull]
    private LocaleEnum $locale = LocaleEnum::EN;

    /**
     * @var TTranslatable
     */
    #[ORM\ManyToOne(targetEntity: TranslatableInterface::class, inversedBy: 'translations')]
    #[ORM\JoinColumn(name: 'translatable_id', nullable: false, onDelete: 'CASCADE')]
    private TranslatableInterface $translatable;

    public function getLocale(): LocaleEnum
    {
        return $this->locale;
    }

    public function setLocale(LocaleEnum $locale): void
    {
        $this->locale = $locale;
    }

    /**
     * @return TTranslatable
     */
    public function getTranslatable(): TranslatableInterface
    {
        return $this->translatable;
    }

    /**
     * @param TTranslatable $translatable
     */
    public function setTranslatable(TranslatableInterface $translatable): void
    {
        $this->translatable = $translatable;
    }
}
