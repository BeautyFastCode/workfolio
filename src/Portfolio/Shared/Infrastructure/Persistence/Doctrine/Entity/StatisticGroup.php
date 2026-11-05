<?php

declare(strict_types=1);

namespace App\Portfolio\Shared\Infrastructure\Persistence\Doctrine\Entity;

use App\Shared\Domain\LocaleEnum;
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
 * @implements TranslatableInterface<StatisticGroupTranslation>
 */
#[ORM\Entity]
#[ORM\Table(name: 'app_statistic_group')]
class StatisticGroup extends BaseEntity implements TranslatableInterface, TimestampableInterface
{
    use TimestampableTrait;

    /**
     * @use TranslatableTrait<StatisticGroupTranslation>
     */
    use TranslatableTrait;

    #[ORM\Column(name: 'position', type: Types::INTEGER, nullable: false)]
    #[Assert\PositiveOrZero]
    private int $position = 0;

    /**
     * @var Collection<int, StatisticItem>
     */
    #[ORM\OneToMany(
        targetEntity: StatisticItem::class,
        mappedBy: 'group',
        cascade: ['persist'],
        orphanRemoval: true
    )]
    #[Assert\Valid]
    private Collection $items;

    /**
     * @var Collection<int, StatisticGroupTranslation>
     */
    #[ORM\OneToMany(
        targetEntity: StatisticGroupTranslation::class,
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
            'StatisticGroup #%s',
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

        if ($translation instanceof StatisticGroupTranslation) {
            return $translation->getName();
        }

        return null;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    /**
     * @return Collection<int, StatisticItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(StatisticItem $item): void
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setGroup($this);
        }
    }

    public function removeItem(StatisticItem $item): void
    {
        $this->items->removeElement($item);
    }
}
