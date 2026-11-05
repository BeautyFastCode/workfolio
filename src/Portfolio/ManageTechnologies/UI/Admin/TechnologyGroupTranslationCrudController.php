<?php

declare(strict_types=1);

namespace App\Portfolio\ManageTechnologies\UI\Admin;

use App\Portfolio\Shared\Infrastructure\Persistence\Doctrine\Entity\TechnologyGroupTranslation;
use App\Shared\Domain\LocaleEnum;
use App\Shared\UI\Admin\AbstractCrudController;
use App\Shared\UI\Admin\LocaleLabels;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\EnumType;

/**
 * @extends AbstractCrudController<TechnologyGroupTranslation>
 */
class TechnologyGroupTranslationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return TechnologyGroupTranslation::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInPlural('crud.technology_group_translation.entity_label.plural')
            ->setEntityLabelInSingular('crud.technology_group_translation.entity_label.singular')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield ChoiceField::new('locale', 'crud.technology_group_translation.locale')
            ->setFormType(EnumType::class)
            ->setFormTypeOptions([
                'class' => LocaleEnum::class,
                'placeholder' => false,
                'choice_label' => LocaleLabels::translationKey(...),
            ])
        ;

        yield TextField::new('name', 'crud.technology_group_translation.name');
    }
}
