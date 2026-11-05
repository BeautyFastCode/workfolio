<?php

declare(strict_types=1);

namespace App\Identity\CreateAdminUser\Application;

interface AdminUserUniquenessChecker
{
    /**
     * @return list<AdminUserConflict>
     */
    public function findConflicts(string $email, string $username): array;
}
