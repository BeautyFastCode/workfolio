<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Doctrine\Behavior;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

trait ToggleableTrait
{
    #[ORM\Column(name: 'enabled', type: Types::BOOLEAN, options: ['default' => false])]
    #[Assert\Type(type: Types::BOOLEAN)]
    #[Assert\NotNull]
    protected bool $enabled = true;

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(?bool $enabled): void
    {
        $this->enabled = (bool) $enabled;
    }

    public function enable(): void
    {
        $this->enabled = true;
    }

    public function disable(): void
    {
        $this->enabled = false;
    }
}
