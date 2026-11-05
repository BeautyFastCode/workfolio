<?php

declare(strict_types=1);

namespace App\Portfolio\ManageStatistics\UI\Admin;

use App\Portfolio\Shared\Infrastructure\Persistence\Doctrine\Entity\StatisticGroup;
use App\Shared\UI\Admin\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * @extends AbstractCrudController<StatisticGroup>
 */
class StatisticGroupCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return StatisticGroup::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->renderContentMaximized()
            ->showEntityActionsInlined()
            ->setEntityLabelInPlural('crud.statistic_group.entity_label.plural')
            ->setEntityLabelInSingular('crud.statistic_group.entity_label.singular')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IntegerField::new('position', 'crud.statistic_group.position');

        yield TextField::new('namePl', 'crud.statistic_group.name_pl')
            ->onlyOnIndex()
        ;

        yield TextField::new('nameEn', 'crud.statistic_group.name_en')
            ->onlyOnIndex()
        ;

        yield DateTimeField::new('createdAt', 'crud.statistic_group.created_at')->onlyOnIndex();

        yield DateTimeField::new('updatedAt', 'crud.statistic_group.updated_at')->onlyOnIndex();

        yield CollectionField::new('translations', 'crud.statistic_group.translations')
            ->onlyOnForms()
            ->useEntryCrudForm(StatisticGroupTranslationCrudController::class)
            ->allowAdd()
            ->allowDelete()
            ->renderExpanded()
            ->setEntryIsComplex()
        ;

        yield CollectionField::new('items', 'crud.statistic_group.items')
            ->useEntryCrudForm(StatisticItemCrudController::class)
            ->setEntryIsComplex()
            ->renderExpanded()
            ->allowAdd()
            ->allowDelete()
            ->setFormTypeOption('by_reference', false)
            ->onlyOnForms()
        ;
    }
}
