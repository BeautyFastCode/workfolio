<?php

declare(strict_types=1);

namespace App\Content\Shared\Infrastructure\Persistence\Doctrine\Entity;

use App\Shared\Domain\LocaleEnum;
use App\Shared\Infrastructure\Persistence\Doctrine\Behavior\TimestampableInterface;
use App\Shared\Infrastructure\Persistence\Doctrine\Behavior\TimestampableTrait;
use App\Shared\Infrastructure\Persistence\Doctrine\Entity\BaseEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'app_block_collection')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_NAME', fields: ['name'])]
#[UniqueEntity(fields: ['name'])]
class BlockCollection extends BaseEntity implements TimestampableInterface
{
    use TimestampableTrait;

    #[ORM\Column(type: Types::STRING, length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 100)]
    #[Assert\Regex(
        pattern: '/^[a-z0-9_]+$/',
        message: 'block_collection.name'
    )]
    private string $name = '';

    /**
     * @var Collection<int, Block>
     */
    #[ORM\OneToMany(
        targetEntity: Block::class,
        mappedBy: 'blockCollection',
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

        $this->blocks = new ArrayCollection();
    }

    public function __toString(): string
    {
        $name = $this->getName();

        if (trim($name) !== '') {
            return $name;
        }

        return sprintf(
            'Collection #%s',
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

    /**
     * @return Collection<int, Block>
     */
    public function getBlocks(): Collection
    {
        return $this->blocks;
    }

    public function addBlock(Block $block): void
    {
        if (!$this->blocks->contains($block)) {
            $this->blocks->add($block);
            $block->setBlockCollection($this);
        }
    }

    public function removeBlock(Block $block): void
    {
        $this->blocks->removeElement($block);
    }

    public function getBlock(string $name): ?Block
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

    public function getBlockImage(string $name, LocaleEnum $locale): ?string
    {
        return $this->getBlock($name)?->getImage($locale) ?? null;
    }
}
