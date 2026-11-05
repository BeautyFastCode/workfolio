<?php

declare(strict_types=1);

namespace App\Content\ManagePages\UI\Admin;

use App\Content\Shared\Infrastructure\Persistence\Doctrine\Entity\PageBlock;
use App\Content\Shared\Infrastructure\Persistence\Doctrine\Enum\BlockType;
use App\Shared\UI\Admin\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\EnumType;

/**
 * @extends AbstractCrudController<PageBlock>
 */
class PageBlockCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return PageBlock::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->renderContentMaximized()
            ->showEntityActionsInlined()
            ->setEntityLabelInPlural('crud.page_block.entity_label.plural')
            ->setEntityLabelInSingular('crud.page_block.entity_label.singular')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IntegerField::new('position', 'crud.page_block.position');

        yield TextField::new('name', 'crud.page_block.name');

        yield ChoiceField::new('type', 'crud.page_block.type')
            ->setFormType(EnumType::class)
            ->setFormTypeOptions([
                'class' => BlockType::class,
                'placeholder' => false,
                'choice_label' => static fn (BlockType $blockType): string => $blockType->translationKey(),
            ])
        ;

        yield DateTimeField::new('createdAt', 'crud.page_block.created_at')->onlyOnIndex();

        yield DateTimeField::new('updatedAt', 'crud.page_block.updated_at')->onlyOnIndex();

        yield CollectionField::new('translations', 'crud.page_block.translations')
            ->onlyOnForms()
            ->useEntryCrudForm(PageBlockTranslationCrudController::class)
            ->allowAdd()
            ->allowDelete()
            ->renderExpanded()
            ->setEntryIsComplex()
        ;
    }
}
