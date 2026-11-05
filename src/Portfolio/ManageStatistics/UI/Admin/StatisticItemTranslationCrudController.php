<?php

declare(strict_types=1);

namespace App\Portfolio\ManageStatistics\UI\Admin;

use App\Portfolio\Shared\Infrastructure\Persistence\Doctrine\Entity\StatisticItemTranslation;
use App\Shared\Domain\LocaleEnum;
use App\Shared\UI\Admin\AbstractCrudController;
use App\Shared\UI\Admin\LocaleLabels;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\EnumType;

/**
 * @extends AbstractCrudController<StatisticItemTranslation>
 */
class StatisticItemTranslationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return StatisticItemTranslation::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInPlural('crud.statistic_item_translation.entity_label.plural')
            ->setEntityLabelInSingular('crud.statistic_item_translation.entity_label.singular')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield ChoiceField::new('locale', 'crud.statistic_item_translation.locale')
            ->setFormType(EnumType::class)
            ->setFormTypeOptions([
                'class' => LocaleEnum::class,
                'placeholder' => false,
                'choice_label' => LocaleLabels::translationKey(...),
            ])
        ;

        yield TextField::new('name', 'crud.statistic_item_translation.name');
    }
}
