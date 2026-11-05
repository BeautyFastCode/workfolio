<?php

declare(strict_types=1);

namespace App\Portfolio\ManageStatistics\UI\Admin;

use App\Portfolio\Shared\Infrastructure\Persistence\Doctrine\Entity\StatisticItem;
use App\Shared\UI\Admin\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * @extends AbstractCrudController<StatisticItem>
 */
class StatisticItemCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return StatisticItem::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->renderContentMaximized()
            ->showEntityActionsInlined()
            ->setEntityLabelInPlural('crud.statistic_item.entity_label.plural')
            ->setEntityLabelInSingular('crud.statistic_item.entity_label.singular')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IntegerField::new('position', 'crud.statistic_item.position');

        yield TextField::new('icon', 'crud.statistic_item.icon')->onlyOnForms();

        yield TextField::new('value', 'crud.statistic_item.value')->onlyOnForms();

        yield TextField::new('namePl', 'crud.statistic_item.name_pl')
            ->onlyOnIndex()
        ;

        yield TextField::new('nameEn', 'crud.statistic_item.name_en')
            ->onlyOnIndex()
        ;

        yield DateTimeField::new('createdAt', 'crud.statistic_item.created_at')->onlyOnIndex();

        yield DateTimeField::new('updatedAt', 'crud.statistic_item.updated_at')->onlyOnIndex();

        yield CollectionField::new('translations', 'crud.statistic_item.translations')
            ->onlyOnForms()
            ->useEntryCrudForm(StatisticItemTranslationCrudController::class)
            ->allowAdd()
            ->allowDelete()
            ->renderExpanded()
            ->setEntryIsComplex()
        ;
    }
}
