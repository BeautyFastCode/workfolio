<?php

declare(strict_types=1);

namespace App\CallToAction\Shared\Infrastructure\Persistence\Doctrine\Entity;

use App\Shared\Domain\LocaleEnum;
use App\Shared\Infrastructure\Persistence\Doctrine\Behavior\TimestampableInterface;
use App\Shared\Infrastructure\Persistence\Doctrine\Behavior\TimestampableTrait;
use App\Shared\Infrastructure\Persistence\Doctrine\Entity\BaseEntity;
use App\Shared\Infrastructure\Persistence\Doctrine\Translation\TranslatableInterface;
use App\Shared\Infrastructure\Persistence\Doctrine\Translation\TranslatableTrait;
use App\Shared\Infrastructure\Persistence\Doctrine\Validation\UniqueTranslationLocales;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @implements TranslatableInterface<CtaMessageTranslation>
 */
#[ORM\Entity]
#[ORM\Table(name: 'app_cta_message')]
class CtaMessage extends BaseEntity implements TranslatableInterface, TimestampableInterface
{
    use TimestampableTrait;

    /**
     * @use TranslatableTrait<CtaMessageTranslation>
     */
    use TranslatableTrait;

    /**
     * @var Collection<int, CtaMessageTranslation>
     */
    #[ORM\OneToMany(
        targetEntity: CtaMessageTranslation::class,
        mappedBy: 'translatable',
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    #[Assert\Count(min: 1, minMessage: 'translatable.translations.min_count')]
    #[UniqueTranslationLocales]
    #[Assert\Valid]
    private Collection $translations;

    public function __construct()
    {
        parent::__construct();

        $this->initializeCreatedAt();

        $this->translations = new ArrayCollection();
    }

    public function __toString(): string
    {
        $message = $this->getMessage(LocaleEnum::EN) ?: $this->getMessage(LocaleEnum::PL);

        if (is_string($message) && trim($message) !== '') {
            return $message;
        }

        return sprintf(
            'CtaMessage #%s',
            $this->id->toString()
        );
    }

    public function getMessagePl(): ?string
    {
        return $this->getMessage(LocaleEnum::PL);
    }

    public function getMessageEn(): ?string
    {
        return $this->getMessage(LocaleEnum::EN);
    }

    public function getMessage(LocaleEnum $locale): ?string
    {
        $translation = $this->getTranslation($locale);

        if ($translation instanceof CtaMessageTranslation) {
            return $translation->getMessage();
        }

        return null;
    }
}
