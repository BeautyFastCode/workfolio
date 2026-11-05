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
 * @implements TranslatableInterface<TaskTranslation>
 */
#[ORM\Entity]
#[ORM\Table(name: 'app_task')]
class Task extends BaseEntity implements PositionableInterface, TranslatableInterface, TimestampableInterface
{
    use PositionableTrait;
    use TimestampableTrait;

    /**
     * @use TranslatableTrait<TaskTranslation>
     */
    use TranslatableTrait;

    #[ORM\Column(name: 'thumbnail', type: Types::STRING, length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $thumbnail = null;

    /**
     * @var Collection<int, TaskTranslation>
     */
    #[ORM\OneToMany(
        targetEntity: TaskTranslation::class,
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
            'Task #%s',
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

        if ($translation instanceof TaskTranslation) {
            return $translation->getName();
        }

        return null;
    }

    public function getDescription(LocaleEnum $locale): ?string
    {
        $translation = $this->getTranslation($locale);

        if ($translation instanceof TaskTranslation) {
            return $translation->getDescription();
        }

        return null;
    }

    public function getTools(LocaleEnum $locale): ?string
    {
        $translation = $this->getTranslation($locale);

        if ($translation instanceof TaskTranslation) {
            return $translation->getTools();
        }

        return null;
    }

    public function getImpact(LocaleEnum $locale): ?string
    {
        $translation = $this->getTranslation($locale);

        if ($translation instanceof TaskTranslation) {
            return $translation->getImpact();
        }

        return null;
    }

    public function getThumbnail(): ?string
    {
        return $this->thumbnail;
    }

    public function setThumbnail(?string $thumbnail): void
    {
        $this->thumbnail = $thumbnail;
    }
}
