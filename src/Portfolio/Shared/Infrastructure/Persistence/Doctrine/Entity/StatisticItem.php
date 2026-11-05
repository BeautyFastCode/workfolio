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
 * @implements TranslatableInterface<StatisticItemTranslation>
 */
#[ORM\Entity]
#[ORM\Table(name: 'app_statistic_item')]
class StatisticItem extends BaseEntity implements TranslatableInterface, TimestampableInterface
{
    use TimestampableTrait;

    /**
     * @use TranslatableTrait<StatisticItemTranslation>
     */
    use TranslatableTrait;

    #[ORM\Column(name: 'icon', type: Types::STRING, length: 64, nullable: true)]
    #[Assert\Length(max: 64)]
    private ?string $icon = null;

    #[ORM\Column(name: 'value', type: Types::STRING, length: 32, nullable: false)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 1, max: 32)]
    private string $value = '';

    #[ORM\Column(name: 'position', type: Types::INTEGER, nullable: false)]
    #[Assert\PositiveOrZero]
    private int $position = 0;

    #[ORM\ManyToOne(inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private StatisticGroup $group;

    /**
     * @var Collection<int, StatisticItemTranslation>
     */
    #[ORM\OneToMany(
        targetEntity: StatisticItemTranslation::class,
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
        $name = $this->getName(LocaleEnum::EN) ?: $this->getName(LocaleEnum::PL);

        if (is_string($name) && trim($name) !== '') {
            return $name;
        }

        return sprintf(
            'StatisticItem #%s',
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

        if ($translation instanceof StatisticItemTranslation) {
            return $translation->getName();
        }

        return null;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): void
    {
        $this->icon = $icon;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    public function getGroup(): StatisticGroup
    {
        return $this->group;
    }

    public function setGroup(StatisticGroup $group): void
    {
        $this->group = $group;
    }
}
