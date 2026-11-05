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
 * @implements TranslationInterface<Page>
 */
#[ORM\Entity]
#[ORM\Table(name: 'app_page_translation')]
#[ORM\UniqueConstraint(
    name: 'uniq_page_translation',
    columns: ['page_id', 'locale']
)]
class PageTranslation extends BaseEntity implements TranslationInterface, TimestampableInterface
{
    /**
     * @use TranslationTrait<Page>
     */
    use TranslationTrait;
    use TimestampableTrait;

    #[ORM\Column(type: Types::STRING, length: 150)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 150)]
    private string $title = '';

    #[ORM\Column(type: Types::STRING, length: 120)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 120)]
    #[Assert\Regex(
        pattern: '/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
        message: 'page_translation.slug'
    )]
    private string $slug = '';

    #[ORM\Column(name: 'menu_title', type: Types::STRING, length: 150, nullable: true)]
    #[Assert\Length(max: 150)]
    private ?string $menuTitle = null;

    /**
     * @var Page
     */
    #[ORM\ManyToOne(targetEntity: Page::class, inversedBy: 'translations')]
    #[ORM\JoinColumn(name: 'page_id', nullable: false, onDelete: 'CASCADE')]
    private TranslatableInterface $translatable;

    public function __construct()
    {
        parent::__construct();

        $this->initializeCreatedAt();
    }

    public function __toString(): string
    {
        $locale = strtoupper($this->locale->value);
        $name = $this->title ?: sprintf(
            'Translation #%s',
            $this->id->toString()
        );

        return sprintf('%s: %s', $locale, $name);
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    public function getMenuTitle(): ?string
    {
        return $this->menuTitle;
    }

    public function setMenuTitle(?string $menuTitle): void
    {
        $this->menuTitle = $menuTitle;
    }
}
