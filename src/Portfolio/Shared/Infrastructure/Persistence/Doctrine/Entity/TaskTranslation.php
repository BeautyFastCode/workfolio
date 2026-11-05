<?php

declare(strict_types=1);

namespace App\Portfolio\Shared\Infrastructure\Persistence\Doctrine\Entity;

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
 * @implements TranslationInterface<Task>
 */
#[ORM\Entity]
#[ORM\Table(name: 'app_task_translation')]
#[ORM\UniqueConstraint(
    name: 'uniq_task_translation',
    columns: ['task_id', 'locale']
)]
class TaskTranslation extends BaseEntity implements TranslationInterface, TimestampableInterface
{
    /**
     * @use TranslationTrait<Task>
     */
    use TranslationTrait;
    use TimestampableTrait;

    #[ORM\Column(name: 'name', type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 255)]
    private string $name = '';

    #[ORM\Column(name: 'description', type: Types::TEXT, nullable: true)]
    #[Assert\Length(min: 10, max: 1000)]
    private ?string $description = null;

    #[ORM\Column(name: 'tools', type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 255)]
    private string $tools = '';

    #[ORM\Column(name: 'impact', type: Types::TEXT)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 10, max: 1000)]
    private string $impact = '';

    /**
     * @var Task
     */
    #[ORM\ManyToOne(targetEntity: Task::class, inversedBy: 'translations')]
    #[ORM\JoinColumn(name: 'task_id', nullable: false, onDelete: 'CASCADE')]
    private TranslatableInterface $translatable;

    public function __construct()
    {
        parent::__construct();

        $this->initializeCreatedAt();
    }

    public function __toString(): string
    {
        $locale = strtoupper($this->locale->value);
        $name = $this->name ?: sprintf(
            'Translation #%s',
            $this->id->toString()
        );

        return sprintf('%s: %s', $locale, $name);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getTools(): string
    {
        return $this->tools;
    }

    public function setTools(string $tools): void
    {
        $this->tools = $tools;
    }

    public function getImpact(): string
    {
        return $this->impact;
    }

    public function setImpact(string $impact): void
    {
        $this->impact = $impact;
    }
}
