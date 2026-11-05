<?php

declare(strict_types=1);

namespace App\Shared\UI\Admin;

use App\Shared\Infrastructure\Persistence\Doctrine\Behavior\TimestampableInterface;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController as EasyCorpAbstractCrudController;

/**
 * @template TEntity of object
 *
 * @extends EasyCorpAbstractCrudController<TEntity>
 */
abstract class AbstractCrudController extends EasyCorpAbstractCrudController
{
    public function persistEntity(EntityManagerInterface $entityManager, object $entityInstance): void
    {
        if (!$entityInstance instanceof TimestampableInterface) {
            parent::persistEntity($entityManager, $entityInstance);

            return;
        }

        $entityInstance->initializeCreatedAt();

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof TimestampableInterface) {
            parent::updateEntity($entityManager, $entityInstance);

            return;
        }

        $entityInstance->touch();

        parent::updateEntity($entityManager, $entityInstance);
    }
}
