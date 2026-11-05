<?php

declare(strict_types=1);

namespace App\Portfolio\Shared\Infrastructure\Persistence\Doctrine\Entity;

use App\Shared\Domain\LocaleEnum;
use App\Shared\Infrastructure\Persistence\Doctrine\Behavior\PositionableInterface;
use App\Shared\Infrastructure\Persistence\Doctrine\Behavior\PositionableTrait;
use App\Shared\Infrastructure\Persistence\Doctrine\Behavior\TimestampableInterface;
use App\Shared\Infrastructure\Persistence\Doctrine\Behavior\TimestampableTrait;
use App\Shared\Infrastructure\Persistence\Doctrine\Entity\BaseEntity;
use App\Shared\Infrastructure\Persistence\Doctrine\Translation\TranslatableInterface;
use App\Shared\Infrastructure\Persistence\Doctrine\Translation\TranslatableTrait;
use App\Shared\Infrastructure\Persistence\Doctrine\Validation\UniqueTranslationLocales;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @implements TranslatableInterface<TechnologyGroupTranslation>
 */
#[ORM\Entity]
#[ORM\Table(name: 'app_technology_group')]
class TechnologyGroup extends BaseEntity implements PositionableInterface, TranslatableInterface, TimestampableInterface
{
    use PositionableTrait;
    use TimestampableTrait;

    /**
     * @use TranslatableTrait<TechnologyGroupTranslation>
     */
    use TranslatableTrait;

    #[ORM\Column(name: 'highlighted', type: Types::BOOLEAN, options: ['default' => false])]
    #[Assert\Type(type: Types::BOOLEAN)]
    #[Assert\NotNull]
    private bool $highlighted = true;

    /**
     * @var Collection<int, TechnologyItem>
     */
    #[ORM\OneToMany(
        targetEntity: TechnologyItem::class,
        mappedBy: 'group',
        cascade: ['persist'],
        orphanRemoval: true
    )]
    #[Assert\Valid]
    private Collection $items;

    /**
     * @var Collection<int, TechnologyGroupTranslation>
     */
    #[ORM\OneToMany(
        targetEntity: TechnologyGroupTranslation::class,
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
        $this->items = new ArrayCollection();
    }

    public function __toString(): string
    {
        $name = $this->getName(LocaleEnum::EN) ?: $this->getName(LocaleEnum::PL);

        if (is_string($name) && trim($name) !== '') {
            return $name;
        }

        return sprintf(
            'TechnologyGroup #%s',
            $this->id->toString()
        );
    }

    public function getNamePl(): ?string
    {
        return $this->getName(LocaleEnum::PL);
    }

    public function getNameEn(): ?string
    {
        return $this->getName(LocaleEnum::EN);
    }

    public function getName(LocaleEnum $locale): ?string
    {
        $translation = $this->getTranslation($locale);

        if ($translation instanceof TechnologyGroupTranslation) {
            return $translation->getName();
        }

        return null;
    }

    public function isHighlighted(): bool
    {
        return $this->highlighted;
    }

    public function setHighlighted(bool $highlighted): void
    {
        $this->highlighted = $highlighted;
    }

    /**
     * @return Collection<int, TechnologyItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(TechnologyItem $item): void
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setGroup($this);
        }
    }

    public function removeItem(TechnologyItem $item): void
    {
        $this->items->removeElement($item);
    }
}
