<?php

declare(strict_types=1);

namespace App\Portfolio\Shared\Infrastructure\Persistence\Doctrine\Entity;

use App\Shared\Infrastructure\Persistence\Doctrine\Behavior\IconableInterface;
use App\Shared\Infrastructure\Persistence\Doctrine\Behavior\IconableTrait;
use App\Shared\Infrastructure\Persistence\Doctrine\Behavior\PositionableInterface;
use App\Shared\Infrastructure\Persistence\Doctrine\Behavior\PositionableTrait;
use App\Shared\Infrastructure\Persistence\Doctrine\Behavior\TimestampableInterface;
use App\Shared\Infrastructure\Persistence\Doctrine\Behavior\TimestampableTrait;
use App\Shared\Infrastructure\Persistence\Doctrine\Entity\BaseEntity;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'app_technology_item')]
class TechnologyItem extends BaseEntity implements IconableInterface, PositionableInterface, TimestampableInterface
{
    use IconableTrait;
    use PositionableTrait;
    use TimestampableTrait;

    #[ORM\Column(name: 'name', type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 255)]
    private string $name = '';

    #[ORM\ManyToOne(inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private TechnologyGroup $group;

    public function __construct()
    {
        parent::__construct();

        $this->initializeCreatedAt();
    }

    public function __toString(): string
    {
        $name = $this->getName();

        if (trim($name) !== '') {
            return $name;
        }

        return sprintf(
            'TechnologyItem #%s',
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

    public function getGroup(): TechnologyGroup
    {
        return $this->group;
    }

    public function setGroup(TechnologyGroup $group): void
    {
        $this->group = $group;
    }
}
