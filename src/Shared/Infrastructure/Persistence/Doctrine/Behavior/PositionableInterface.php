<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Doctrine\Behavior;

interface PositionableInterface
{
    public function getPosition(): int;

    public function setPosition(int $position): void;
}
