<?php

declare(strict_types=1);

namespace App\Content\Shared\Infrastructure\Persistence\Doctrine\Entity;

use App\Content\Shared\Infrastructure\Persistence\Doctrine\Enum\BlockType;
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
 * @implements TranslatableInterface<PageBlockTranslation>
 */
#[ORM\Entity]
#[ORM\Table(name: 'app_page_block')]
#[ORM\UniqueConstraint(name: 'uniq_app_page_block_name', columns: ['page_id', 'name'])]
class PageBlock extends BaseEntity implements PositionableInterface, TranslatableInterface, TimestampableInterface
{
    use PositionableTrait;
    use TimestampableTrait;

    /**
     * @use TranslatableTrait<PageBlockTranslation>
     */
    use TranslatableTrait;

    #[ORM\Column(type: Types::STRING, length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 100)]
    #[Assert\Regex(
        pattern: '/^[a-z0-9_]+$/',
        message: 'page_block.name'
    )]
    private string $name = '';

    #[ORM\Column(type: Types::ENUM, length: 50, enumType: BlockType::class)]
    #[Assert\NotBlank]
    private BlockType $type = BlockType::TEXT;

    #[ORM\ManyToOne(targetEntity: Page::class, inversedBy: 'blocks')]
    #[ORM\JoinColumn(name: 'page_id', nullable: false, onDelete: 'CASCADE')]
    private Page $page;

    /**
     * @var Collection<int, PageBlockTranslation>
     */
    #[ORM\OneToMany(
        targetEntity: PageBlockTranslation::class,
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
        if (trim($this->name) !== '') {
            return $this->name;
        }

        return sprintf(
            'PageBlock #%s',
            $this->id->toString()
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getType(): BlockType
    {
        return $this->type;
    }

    public function setType(BlockType $type): void
    {
        $this->type = $type;
    }

    public function getPage(): Page
    {
        return $this->page;
    }

    public function setPage(Page $page): void
    {
        $this->page = $page;
    }

    public function getContent(LocaleEnum $locale): ?string
    {
        $translation = $this->getTranslation($locale);

        if ($translation instanceof PageBlockTranslation) {
            return $translation->getContent();
        }

        return null;
    }
}
