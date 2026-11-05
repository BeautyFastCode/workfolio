<?php

declare(strict_types=1);

namespace App\Shared\UI\Admin;

use App\Identity\Shared\Application\AdminUserRoleEnum;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

/**
 * @template TEntity of object
 *
 * @extends AbstractCrudController<TEntity>
 */
abstract class AbstractSecuredCrudController extends AbstractCrudController
{
    public function configureActions(Actions $actions): Actions
    {
        foreach ($this->getProtectedActions() as $action) {
            $actions->setPermission($action, AdminUserRoleEnum::ADMIN->value);
        }

        return $actions;
    }

    /**
     * @return string[]
     */
    protected function getProtectedActions(): array
    {
        return [
            Action::INDEX,
            Action::DETAIL,
            Action::NEW,
            Action::EDIT,
            Action::DELETE,
            Action::BATCH_DELETE,
        ];
    }
}
