<?php

declare(strict_types=1);

namespace App\Identity\CreateAdminUser\Application;

enum AdminUserConflict: string
{
    case EMAIL = 'email';
    case USERNAME = 'username';
}
