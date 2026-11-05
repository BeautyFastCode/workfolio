<?php

declare(strict_types=1);

namespace App\Identity\ManageAdminUsers\UI\Admin;

use App\Identity\Shared\Application\AdminUserRoleEnum;

final class AdminUserRoleLabels
{
    /**
     * @return array<string, string>
     */
    public static function choices(): array
    {
        return [
            'crud.admin_user.roles_choices.user' => AdminUserRoleEnum::USER->value,
            'crud.admin_user.roles_choices.admin' => AdminUserRoleEnum::ADMIN->value,
            'crud.admin_user.roles_choices.super_admin' => AdminUserRoleEnum::SUPER_ADMIN->value,
        ];
    }
}
