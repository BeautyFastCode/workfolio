<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Doctrine\Entity;

use Symfony\Component\Uid\Uuid;

interface ResourceInterface
{
    public function getId(): Uuid;
}
