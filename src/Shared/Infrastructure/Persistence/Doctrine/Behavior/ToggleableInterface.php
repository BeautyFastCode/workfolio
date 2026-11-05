<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Doctrine\Behavior;

interface ToggleableInterface
{
    public function isEnabled(): bool;

    public function setEnabled(?bool $enabled): void;

    public function enable(): void;

    public function disable(): void;
}
