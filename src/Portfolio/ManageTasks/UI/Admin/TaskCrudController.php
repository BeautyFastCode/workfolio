<?php

declare(strict_types=1);

namespace App\Portfolio\ManageTasks\UI\Admin;

use App\Portfolio\Shared\Infrastructure\Persistence\Doctrine\Entity\Task;
use App\Shared\UI\Admin\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * @extends AbstractCrudController<Task>
 */
class TaskCrudController extends AbstractCrudController
{
    public function __construct(
        #[Autowire(param: 'app.image.base_path')]
        private readonly string $basePath,
        #[Autowire(param: 'app.image.upload_dir')]
        private readonly string $uploadDir
    ) {}

    public static function getEntityFqcn(): string
    {
        return Task::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->renderContentMaximized()
            ->showEntityActionsInlined()
            ->setEntityLabelInPlural('crud.task.entity_label.plural')
            ->setEntityLabelInSingular('crud.task.entity_label.singular')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IntegerField::new('position', 'crud.task.position');

        yield ImageField::new('thumbnail', 'crud.task.thumbnail')
            ->setBasePath('/'.$this->basePath)
            ->setUploadDir('/'.$this->uploadDir)
            ->setUploadedFileNamePattern('[YYYY]/[MM]/[DD]/[ulid].[extension]')
            ->setRequired(false)
            ->setFormTypeOption('attr', [
                'accept' => 'image/jpeg,image/png,image/webp',
            ])
            ->mimeTypes('image/jpeg,image/png,image/webp')
            ->maxSize('2M')
            ->setHelp('JPG, PNG, WEBP, max. 2 MB.')
        ;

        yield TextField::new('namePl', 'crud.task.name_pl')
            ->onlyOnIndex()
        ;

        yield TextField::new('nameEn', 'crud.task.name_en')
            ->onlyOnIndex()
        ;

        yield DateTimeField::new('createdAt', 'crud.task.created_at')->onlyOnIndex();

        yield DateTimeField::new('updatedAt', 'crud.task.updated_at')->onlyOnIndex();

        yield CollectionField::new('translations', 'crud.task.translations')
            ->onlyOnForms()
            ->useEntryCrudForm(TaskTranslationCrudController::class)
            ->allowAdd()
            ->allowDelete()
            ->renderExpanded()
            ->setEntryIsComplex()
        ;
    }
}
