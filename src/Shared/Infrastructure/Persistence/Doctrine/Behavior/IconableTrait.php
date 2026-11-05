<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Doctrine\Behavior;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

trait IconableTrait
{
    #[ORM\Column(name: 'icon', type: Types::STRING, length: 64, nullable: true)]
    #[Assert\Length(max: 64)]
    private ?string $icon = null;

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): void
    {
        $this->icon = $icon;
    }
}
