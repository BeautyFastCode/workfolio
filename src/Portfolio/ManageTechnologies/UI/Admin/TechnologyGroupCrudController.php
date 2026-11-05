<?php

declare(strict_types=1);

namespace App\Portfolio\ManageTechnologies\UI\Admin;

use App\Portfolio\Shared\Infrastructure\Persistence\Doctrine\Entity\TechnologyGroup;
use App\Shared\UI\Admin\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * @extends AbstractCrudController<TechnologyGroup>
 */
class TechnologyGroupCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return TechnologyGroup::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->renderContentMaximized()
            ->showEntityActionsInlined()
            ->setEntityLabelInPlural('crud.technology_group.entity_label.plural')
            ->setEntityLabelInSingular('crud.technology_group.entity_label.singular')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IntegerField::new('position', 'crud.technology_group.position');

        yield BooleanField::new('highlighted', 'crud.technology_group.highlighted');

        yield TextField::new('namePl', 'crud.technology_group.name_pl')
            ->onlyOnIndex()
        ;

        yield TextField::new('nameEn', 'crud.technology_group.name_en')
            ->onlyOnIndex()
        ;

        yield DateTimeField::new('createdAt', 'crud.technology_group.created_at')->onlyOnIndex();

        yield DateTimeField::new('updatedAt', 'crud.technology_group.updated_at')->onlyOnIndex();

        yield CollectionField::new('translations', 'crud.technology_group.translations')
            ->onlyOnForms()
            ->useEntryCrudForm(TechnologyGroupTranslationCrudController::class)
            ->allowAdd()
            ->allowDelete()
            ->renderExpanded()
            ->setEntryIsComplex()
        ;

        yield CollectionField::new('items', 'crud.technology_group.items')
            ->useEntryCrudForm(TechnologyItemCrudController::class)
            ->setEntryIsComplex()
            ->renderExpanded()
            ->allowAdd()
            ->allowDelete()
            ->setFormTypeOption('by_reference', false)
            ->onlyOnForms()
        ;
    }
}
