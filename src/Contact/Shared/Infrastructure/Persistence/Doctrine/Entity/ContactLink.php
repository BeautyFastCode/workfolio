<?php

declare(strict_types=1);

namespace App\Contact\Shared\Infrastructure\Persistence\Doctrine\Entity;

use App\Shared\Infrastructure\Persistence\Doctrine\Behavior\IconableInterface;
use App\Shared\Infrastructure\Persistence\Doctrine\Behavior\IconableTrait;
use App\Shared\Infrastructure\Persistence\Doctrine\Behavior\PositionableInterface;
use App\Shared\Infrastructure\Persistence\Doctrine\Behavior\PositionableTrait;
use App\Shared\Infrastructure\Persistence\Doctrine\Behavior\TimestampableInterface;
use App\Shared\Infrastructure\Persistence\Doctrine\Behavior\TimestampableTrait;
use App\Shared\Infrastructure\Persistence\Doctrine\Behavior\ToggleableInterface;
use App\Shared\Infrastructure\Persistence\Doctrine\Behavior\ToggleableTrait;
use App\Shared\Infrastructure\Persistence\Doctrine\Entity\BaseEntity;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'app_contact_link')]
class ContactLink extends BaseEntity implements ToggleableInterface, PositionableInterface, IconableInterface, TimestampableInterface
{
    use ToggleableTrait;
    use PositionableTrait;
    use IconableTrait;
    use TimestampableTrait;

    #[ORM\Column(name: 'name', type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 1, max: 255)]
    private string $name = '';

    #[ORM\Column(name: 'href', type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $href;

    public function __construct()
    {
        parent::__construct();

        $this->initializeCreatedAt();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getHref(): string
    {
        return $this->href;
    }

    public function setHref(string $href): void
    {
        $this->href = $href;
    }
}
