<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Doctrine\Behavior;

interface SoftDeletableInterface
{
    public function isDeleted(): bool;

    public function getDeletedAt(): ?\DateTimeImmutable;

    public function setDeletedAt(?\DateTimeImmutable $deletedAt): void;

    public function delete(): void;

    public function restore(): void;
}
