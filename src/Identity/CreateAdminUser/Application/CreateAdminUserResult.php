<?php

declare(strict_types=1);

namespace App\Identity\CreateAdminUser\Application;

final readonly class CreateAdminUserResult
{
    /**
     * @param list<AdminUserConflict> $conflicts
     */
    private function __construct(
        public ?string $administratorId,
        public array $conflicts,
    ) {}

    /**
     * @param non-empty-string $administratorId
     */
    public static function created(string $administratorId): self
    {
        return new self($administratorId, []);
    }

    /**
     * @param non-empty-list<AdminUserConflict> $conflicts
     */
    public static function conflicted(array $conflicts): self
    {
        return new self(null, $conflicts);
    }

    public function wasCreated(): bool
    {
        return $this->administratorId !== null;
    }
}
