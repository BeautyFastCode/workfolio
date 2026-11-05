<?php

declare(strict_types=1);

namespace App\Portfolio\ManageTechnologies\UI\Admin;

use App\Portfolio\Shared\Infrastructure\Persistence\Doctrine\Entity\TechnologyItem;
use App\Shared\UI\Admin\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * @extends AbstractCrudController<TechnologyItem>
 */
class TechnologyItemCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return TechnologyItem::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->renderContentMaximized()
            ->showEntityActionsInlined()
            ->setEntityLabelInPlural('crud.technology_item.entity_label.plural')
            ->setEntityLabelInSingular('crud.technology_item.entity_label.singular')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name', 'crud.technology_item.name');

        yield IntegerField::new('position', 'crud.technology_item.position');

        yield TextField::new('icon', 'crud.technology_item.icon')->onlyOnForms();

        yield DateTimeField::new('createdAt', 'crud.technology_item.created_at')->onlyOnIndex();

        yield DateTimeField::new('updatedAt', 'crud.technology_item.updated_at')->onlyOnIndex();
    }
}
