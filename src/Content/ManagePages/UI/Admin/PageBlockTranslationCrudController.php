<?php

declare(strict_types=1);

namespace App\Content\ManagePages\UI\Admin;

use App\Content\Shared\Infrastructure\Persistence\Doctrine\Entity\PageBlockTranslation;
use App\Shared\Domain\LocaleEnum;
use App\Shared\UI\Admin\AbstractCrudController;
use App\Shared\UI\Admin\LocaleLabels;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use Symfony\Component\Form\Extension\Core\Type\EnumType;

/**
 * @extends AbstractCrudController<PageBlockTranslation>
 */
class PageBlockTranslationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return PageBlockTranslation::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInPlural('crud.page_block_translation.entity_label.plural')
            ->setEntityLabelInSingular('crud.page_block_translation.entity_label.singular')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield ChoiceField::new('locale', 'crud.page_block_translation.locale')
            ->setFormType(EnumType::class)
            ->setFormTypeOptions([
                'class' => LocaleEnum::class,
                'placeholder' => false,
                'choice_label' => LocaleLabels::translationKey(...),
            ])
        ;

        yield TextareaField::new('content', 'crud.page_block_translation.content');
    }
}
