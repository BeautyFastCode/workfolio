<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Doctrine\Behavior;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

trait TimestampableTrait
{
    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    #[Assert\Type(type: \DateTimeImmutable::class)]
    #[Assert\NotNull]
    protected \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Assert\Type(type: \DateTimeImmutable::class)]
    protected ?\DateTimeImmutable $updatedAt = null;

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function initializeCreatedAt(?\DateTimeImmutable $date = null): void
    {
        $this->createdAt = $date ?? new \DateTimeImmutable();
    }

    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
