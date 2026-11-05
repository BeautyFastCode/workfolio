<?php

declare(strict_types=1);

namespace App\Tests\Shared\UI\Admin;

use App\Identity\Shared\Application\AdminUserRoleEnum;
use App\Identity\Shared\Infrastructure\Persistence\Doctrine\Entity\AdminUser;
use App\Shared\UI\Admin\AbstractSecuredCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class AbstractSecuredCrudControllerTest extends TestCase
{
    public function test_all_existing_crud_actions_require_the_administrator_role(): void
    {
        $controller = new class extends AbstractSecuredCrudController {
            public static function getEntityFqcn(): string
            {
                return AdminUser::class;
            }
        };

        $permissions = $controller
            ->configureActions(Actions::new())
            ->getAsDto(null)
            ->getActionPermissions()
        ;

        self::assertSame([
            Action::INDEX => AdminUserRoleEnum::ADMIN->value,
            Action::DETAIL => AdminUserRoleEnum::ADMIN->value,
            Action::NEW => AdminUserRoleEnum::ADMIN->value,
            Action::EDIT => AdminUserRoleEnum::ADMIN->value,
            Action::DELETE => AdminUserRoleEnum::ADMIN->value,
            Action::BATCH_DELETE => AdminUserRoleEnum::ADMIN->value,
        ], $permissions);
    }
}
