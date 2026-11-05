<?php

declare(strict_types=1);

namespace App\Identity\CreateAdminUser\Application;

interface AdminUserValidator
{
    /**
     * @return array<string, non-empty-list<string>> Violations grouped by input field
     */
    public function validate(CreateAdminUserCommand $command): array;
}
