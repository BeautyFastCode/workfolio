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
 * @implements TranslatableInterface<BlockTranslation>
 */
#[ORM\Entity]
#[ORM\Table(name: 'app_block')]
#[ORM\UniqueConstraint(name: 'uniq_app_block_name', columns: ['block_collection_id', 'name'])]
class Block extends BaseEntity implements PositionableInterface, TranslatableInterface, TimestampableInterface
{
    use PositionableTrait;
    use TimestampableTrait;

    /**
     * @use TranslatableTrait<BlockTranslation>
     */
    use TranslatableTrait;

    #[ORM\Column(type: Types::STRING, length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 100)]
    #[Assert\Regex(
        pattern: '/^[a-z0-9_]+$/',
        message: 'block.name'
    )]
    private string $name = '';

    #[ORM\Column(type: Types::ENUM, length: 50, enumType: BlockType::class)]
    #[Assert\NotBlank]
    private BlockType $type = BlockType::TEXT;

    #[ORM\ManyToOne(targetEntity: BlockCollection::class, inversedBy: 'blocks')]
    #[ORM\JoinColumn(name: 'block_collection_id', nullable: false, onDelete: 'CASCADE')]
    private BlockCollection $blockCollection;

    /**
     * @var Collection<int, BlockTranslation>
     */
    #[ORM\OneToMany(
        targetEntity: BlockTranslation::class,
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
            'Block #%s',
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

    public function getBlockCollection(): BlockCollection
    {
        return $this->blockCollection;
    }

    public function setBlockCollection(BlockCollection $blockCollection): void
    {
        $this->blockCollection = $blockCollection;
    }

    public function getContent(LocaleEnum $locale): ?string
    {
        $translation = $this->getTranslation($locale);

        if ($translation instanceof BlockTranslation) {
            return $translation->getContent();
        }

        return null;
    }

    public function getImage(LocaleEnum $locale): ?string
    {
        $translation = $this->getTranslation($locale);

        if ($translation instanceof BlockTranslation) {
            return $translation->getImage();
        }

        return null;
    }
}
