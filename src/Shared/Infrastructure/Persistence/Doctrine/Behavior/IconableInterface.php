<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Doctrine\Behavior;

interface IconableInterface
{
    public function getIcon(): ?string;

    public function setIcon(?string $icon): void;
}
