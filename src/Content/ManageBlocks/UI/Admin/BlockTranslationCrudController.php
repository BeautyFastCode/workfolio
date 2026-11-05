<?php

declare(strict_types=1);

namespace App\Content\ManageBlocks\UI\Admin;

use App\Content\Shared\Infrastructure\Persistence\Doctrine\Entity\BlockTranslation;
use App\Shared\Domain\LocaleEnum;
use App\Shared\UI\Admin\AbstractCrudController;
use App\Shared\UI\Admin\LocaleLabels;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\Extension\Core\Type\EnumType;

/**
 * @extends AbstractCrudController<BlockTranslation>
 */
class BlockTranslationCrudController extends AbstractCrudController
{
    public function __construct(
        #[Autowire(param: 'app.image.base_path')]
        private readonly string $basePath,
        #[Autowire(param: 'app.image.upload_dir')]
        private readonly string $uploadDir
    ) {}

    public static function getEntityFqcn(): string
    {
        return BlockTranslation::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInPlural('crud.block_translation.entity_label.plural')
            ->setEntityLabelInSingular('crud.block_translation.entity_label.singular')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield ChoiceField::new('locale', 'crud.block_translation.locale')
            ->setFormType(EnumType::class)
            ->setFormTypeOptions([
                'class' => LocaleEnum::class,
                'placeholder' => false,
                'choice_label' => LocaleLabels::translationKey(...),
            ])
        ;

        yield TextareaField::new('content', 'crud.block_translation.content');

        yield ImageField::new('image', 'crud.block_translation.image')
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
    }
}
