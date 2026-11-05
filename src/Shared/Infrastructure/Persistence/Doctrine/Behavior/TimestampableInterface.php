<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Doctrine\Behavior;

interface TimestampableInterface
{
    public function getCreatedAt(): \DateTimeImmutable;

    public function setCreatedAt(\DateTimeImmutable $createdAt): void;

    public function getUpdatedAt(): ?\DateTimeImmutable;

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): void;

    public function initializeCreatedAt(?\DateTimeImmutable $date = null): void;

    public function touch(): void;
}
