<?php

declare(strict_types=1);

namespace App\Identity\CreateAdminUser\Application;

final readonly class NewAdminUser
{
    /**
     * @param list<string> $roles
     */
    public function __construct(
        public string $email,
        public string $username,
        public string $passwordHash,
        public array $roles,
        public bool $enabled,
    ) {}
}
