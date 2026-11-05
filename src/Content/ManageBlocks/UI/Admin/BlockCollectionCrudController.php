<?php

declare(strict_types=1);

namespace App\Content\ManageBlocks\UI\Admin;

use App\Content\Shared\Infrastructure\Persistence\Doctrine\Entity\BlockCollection;
use App\Shared\UI\Admin\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Asset;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * @extends AbstractCrudController<BlockCollection>
 */
class BlockCollectionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BlockCollection::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->renderContentMaximized()
            ->showEntityActionsInlined()
            ->setEntityLabelInPlural('crud.block_collection.entity_label.plural')
            ->setEntityLabelInSingular('crud.block_collection.entity_label.singular')
        ;
    }

    public function configureAssets(Assets $assets): Assets
    {
        return $assets
            ->addJsFile(
                Asset::fromEasyAdminAssetPackage('field-file-upload.js')
                    ->onlyOnForms()
            )
            ->addJsFile(
                Asset::fromEasyAdminAssetPackage('field-image.js')
                    ->onlyOnForms()
            )
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name', 'crud.block_collection.name');

        yield DateTimeField::new('createdAt', 'crud.block_collection.created_at')->onlyOnIndex();

        yield DateTimeField::new('updatedAt', 'crud.block_collection.updated_at')->onlyOnIndex();

        yield CollectionField::new('blocks', 'crud.block_collection.blocks')
            ->onlyOnForms()
            ->useEntryCrudForm(BlockCrudController::class)
            ->allowAdd()
            ->allowDelete()
            ->renderExpanded()
            ->setEntryIsComplex()
        ;
    }
}
