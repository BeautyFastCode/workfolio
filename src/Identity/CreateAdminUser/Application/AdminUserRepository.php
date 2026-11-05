<?php

declare(strict_types=1);

namespace App\Identity\CreateAdminUser\Application;

interface AdminUserRepository
{
    /**
     * @return non-empty-string Identifier of the persisted administrator
     */
    public function save(NewAdminUser $administrator): string;
}
