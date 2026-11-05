<?php

declare(strict_types=1);

namespace App\Identity\CreateAdminUser\Application;

final class AdminUserPersistenceFailed extends \RuntimeException
{
    public function __construct(\Throwable $previous)
    {
        parent::__construct('Failed to persist the administrator.', previous: $previous);
    }
}
