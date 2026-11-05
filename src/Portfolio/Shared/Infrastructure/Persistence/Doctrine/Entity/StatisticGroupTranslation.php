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
 * @implements TranslationInterface<StatisticGroup>
 */
#[ORM\Entity]
#[ORM\Table(name: 'app_statistic_group_translation')]
#[ORM\UniqueConstraint(
    name: 'uniq_statistic_group_translation',
    columns: ['statistic_group_id', 'locale']
)]
class StatisticGroupTranslation extends BaseEntity implements TranslationInterface, TimestampableInterface
{
    /**
     * @use TranslationTrait<StatisticGroup>
     */
    use TranslationTrait;
    use TimestampableTrait;

    #[ORM\Column(name: 'name', type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 10, max: 255)]
    private string $name = '';

    /**
     * @var StatisticGroup
     */
    #[ORM\ManyToOne(targetEntity: StatisticGroup::class, inversedBy: 'translations')]
    #[ORM\JoinColumn(name: 'statistic_group_id', nullable: false, onDelete: 'CASCADE')]
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
}
