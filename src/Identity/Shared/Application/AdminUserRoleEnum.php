<?php

declare(strict_types=1);

namespace App\Identity\Shared\Application;

enum AdminUserRoleEnum: string
{
    case USER = 'ROLE_USER';
    case ADMIN = 'ROLE_ADMIN';
    case SUPER_ADMIN = 'ROLE_SUPER_ADMIN';
}
