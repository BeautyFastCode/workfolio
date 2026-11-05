<?php

declare(strict_types=1);

namespace App\Content\Shared\Infrastructure\Persistence\Doctrine\Entity;

use App\Shared\Infrastructure\Persistence\Doctrine\Behavior\TimestampableInterface;
use App\Shared\Infrastructure\Persistence\Doctrine\Behavior\TimestampableTrait;
use App\Shared\Infrastructure\Persistence\Doctrine\Entity\BaseEntity;
use App\Shared\Infrastructure\Persistence\Doctrine\Translation\TranslatableInterface;
use App\Shared\Infrastructure\Persistence\Doctrine\Translation\TranslationInterface;
use App\Shared\Infrastructure\Persistence\Doctrine\Translation\TranslationTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @implements TranslationInterface<Block>
 */
#[ORM\Entity]
#[ORM\Table(name: 'app_block_translation')]
#[ORM\UniqueConstraint(
    name: 'uniq_block_translation',
    columns: ['block_id', 'locale']
)]
class BlockTranslation extends BaseEntity implements TranslationInterface, TimestampableInterface
{
    /**
     * @use TranslationTrait<Block>
     */
    use TranslationTrait;
    use TimestampableTrait;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    private string $content;

    #[ORM\Column(name: 'image', type: Types::STRING, length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $image = null;

    /**
     * @var Block
     */
    #[ORM\ManyToOne(targetEntity: Block::class, inversedBy: 'translations')]
    #[ORM\JoinColumn(name: 'block_id', nullable: false, onDelete: 'CASCADE')]
    private TranslatableInterface $translatable;

    public function __construct()
    {
        parent::__construct();

        $this->initializeCreatedAt();
    }

    public function __toString(): string
    {
        $locale = strtoupper($this->locale->value);
        $name = sprintf(
            'Translation #%s',
            $this->id->toString()
        );

        return sprintf('%s: %s', $locale, $name);
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): void
    {
        $this->image = $image;
    }
}
