<?php

declare(strict_types=1);

namespace App\Identity\CreateAdminUser\Application;

interface PasswordHasher
{
    public function hash(string $plainPassword): string;
}
