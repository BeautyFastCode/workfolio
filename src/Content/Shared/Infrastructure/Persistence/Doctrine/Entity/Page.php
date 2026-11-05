<?php

declare(strict_types=1);

namespace App\Content\Shared\Infrastructure\Persistence\Doctrine\Entity;

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
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @implements TranslatableInterface<PageTranslation>
 */
#[ORM\Entity]
#[ORM\Table(name: 'app_page')]
class Page extends BaseEntity implements PositionableInterface, TranslatableInterface, TimestampableInterface
{
    use PositionableTrait;
    use TimestampableTrait;

    /**
     * @use TranslatableTrait<PageTranslation>
     */
    use TranslatableTrait;

    #[ORM\Column(name: 'content_template', type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $contentTemplate;

    #[ORM\Column(name: 'published', type: Types::BOOLEAN, options: ['default' => false])]
    #[Assert\NotNull]
    private bool $published = false;

    /**
     * @var Collection<int, PageTranslation>
     */
    #[ORM\OneToMany(
        targetEntity: PageTranslation::class,
        mappedBy: 'translatable',
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    #[Assert\Count(min: 1, minMessage: 'translatable.translations.min_count')]
    #[UniqueTranslationLocales]
    #[Assert\Valid]
    private Collection $translations;

    /**
     * @var Collection<int, PageBlock>
     */
    #[ORM\OneToMany(
        targetEntity: PageBlock::class,
        mappedBy: 'page',
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    #[ORM\OrderBy(['position' => \SortDirection::Ascending])]
    #[Assert\Valid]
    private Collection $blocks;

    public function __construct()
    {
        parent::__construct();

        $this->initializeCreatedAt();

        $this->translations = new ArrayCollection();
        $this->blocks = new ArrayCollection();
    }

    public function __toString(): string
    {
        $name = $this->getTitle(LocaleEnum::EN) ?: $this->getTitle(LocaleEnum::PL);

        if (is_string($name) && trim($name) !== '') {
            return $name;
        }

        return sprintf(
            'Page #%s',
            $this->id->toString()
        );
    }

    public function getTitlePl(): ?string
    {
        return $this->getTitle(LocaleEnum::PL);
    }

    public function getTitleEn(): ?string
    {
        return $this->getTitle(LocaleEnum::EN);
    }

    public function getTitle(LocaleEnum $locale): ?string
    {
        $translation = $this->getTranslation($locale);

        if ($translation instanceof PageTranslation) {
            return $translation->getTitle();
        }

        return null;
    }

    public function getSlug(LocaleEnum $locale): ?string
    {
        $translation = $this->getTranslation($locale);

        if ($translation instanceof PageTranslation) {
            return $translation->getSlug();
        }

        return null;
    }

    public function getMenuTitleEn(): ?string
    {
        return $this->getMenuTitle(LocaleEnum::PL);
    }

    public function getMenuTitlePl(): ?string
    {
        return $this->getMenuTitle(LocaleEnum::EN);
    }

    public function getMenuTitle(LocaleEnum $locale): ?string
    {
        $translation = $this->getTranslation($locale);

        if ($translation instanceof PageTranslation) {
            return $translation->getMenuTitle() ?: $translation->getTitle();
        }

        return null;
    }

    public function getContentTemplate(): string
    {
        return $this->contentTemplate;
    }

    public function setContentTemplate(string $contentTemplate): void
    {
        $this->contentTemplate = $contentTemplate;
    }

    public function isPublished(): bool
    {
        return $this->published;
    }

    public function setPublished(bool $published): void
    {
        $this->published = $published;
    }

    /**
     * @return Collection<int, PageBlock>
     */
    public function getBlocks(): Collection
    {
        return $this->blocks;
    }

    public function addBlock(PageBlock $block): void
    {
        if (!$this->blocks->contains($block)) {
            $this->blocks->add($block);
            $block->setPage($this);
        }
    }

    public function removeBlock(PageBlock $block): void
    {
        $this->blocks->removeElement($block);
    }

    public function getBlock(string $name): ?PageBlock
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('name', $name))
            ->setMaxResults(1)
        ;

        return $this->blocks->matching($criteria)->first() ?: null;
    }

    public function getBlockContent(string $name, LocaleEnum $locale): ?string
    {
        return $this->getBlock($name)?->getContent($locale) ?? null;
    }
}
