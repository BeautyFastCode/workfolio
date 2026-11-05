<?php

declare(strict_types=1);

namespace App\Portfolio\ManageTasks\UI\Admin;

use App\Portfolio\Shared\Infrastructure\Persistence\Doctrine\Entity\TaskTranslation;
use App\Shared\Domain\LocaleEnum;
use App\Shared\UI\Admin\AbstractCrudController;
use App\Shared\UI\Admin\LocaleLabels;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\EnumType;

/**
 * @extends AbstractCrudController<TaskTranslation>
 */
class TaskTranslationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return TaskTranslation::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInPlural('crud.task_translation.entity_label.plural')
            ->setEntityLabelInSingular('crud.task_translation.entity_label.singular')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield ChoiceField::new('locale', 'crud.task_translation.locale')
            ->setFormType(EnumType::class)
            ->setFormTypeOptions([
                'class' => LocaleEnum::class,
                'placeholder' => false,
                'choice_label' => LocaleLabels::translationKey(...),
            ])
        ;

        yield TextField::new('name', 'crud.task_translation.name');

        yield TextareaField::new('description', 'crud.task_translation.description');

        yield TextField::new('tools', 'crud.task_translation.tools');

        yield TextareaField::new('impact', 'crud.task_translation.impact');
    }
}
