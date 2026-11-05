<?php

declare(strict_types=1);

namespace App\CallToAction\ManageCtaMessages\UI\Admin;

use App\CallToAction\Shared\Infrastructure\Persistence\Doctrine\Entity\CtaMessageTranslation;
use App\Shared\Domain\LocaleEnum;
use App\Shared\UI\Admin\AbstractCrudController;
use App\Shared\UI\Admin\LocaleLabels;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\EnumType;

/**
 * @extends AbstractCrudController<CtaMessageTranslation>
 */
class CtaMessageTranslationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return CtaMessageTranslation::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInPlural('crud.cta_message_translation.entity_label.plural')
            ->setEntityLabelInSingular('crud.cta_message_translation.entity_label.singular')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield ChoiceField::new('locale', 'crud.cta_message_translation.locale')
            ->setFormType(EnumType::class)
            ->setFormTypeOptions([
                'class' => LocaleEnum::class,
                'placeholder' => false,
                'choice_label' => LocaleLabels::translationKey(...),
            ])
        ;

        yield TextField::new('message', 'crud.cta_message_translation.message');
    }
}
