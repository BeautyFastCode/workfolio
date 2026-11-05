<?php

declare(strict_types=1);

namespace App\Content\ManagePages\UI\Admin;

use App\Content\Shared\Infrastructure\Persistence\Doctrine\Entity\PageTranslation;
use App\Shared\Domain\LocaleEnum;
use App\Shared\UI\Admin\AbstractCrudController;
use App\Shared\UI\Admin\LocaleLabels;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\EnumType;

/**
 * @extends AbstractCrudController<PageTranslation>
 */
class PageTranslationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return PageTranslation::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInPlural('crud.page_translation.entity_label.plural')
            ->setEntityLabelInSingular('crud.page_translation.entity_label.singular')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield ChoiceField::new('locale', 'crud.page_translation.locale')
            ->setFormType(EnumType::class)
            ->setFormTypeOptions([
                'class' => LocaleEnum::class,
                'placeholder' => false,
                'choice_label' => LocaleLabels::translationKey(...),
            ])
        ;

        yield TextField::new('title', 'crud.page_translation.title');

        yield TextField::new('slug', 'crud.page_translation.slug');

        yield TextField::new('menuTitle', 'crud.page_translation.menuTitle');
    }
}
