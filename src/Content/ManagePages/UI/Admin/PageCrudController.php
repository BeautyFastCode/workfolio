<?php

declare(strict_types=1);

namespace App\Content\ManagePages\UI\Admin;

use App\Content\Shared\Infrastructure\Persistence\Doctrine\Entity\Page;
use App\Shared\UI\Admin\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * @extends AbstractCrudController<Page>
 */
class PageCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Page::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->renderContentMaximized()
            ->showEntityActionsInlined()
            ->setEntityLabelInPlural('crud.page.entity_label.plural')
            ->setEntityLabelInSingular('crud.page.entity_label.singular')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IntegerField::new('position', 'crud.page.position');

        yield BooleanField::new('published', 'crud.page.published');

        yield TextField::new('menuTitlePl', 'crud.page.title_pl')
            ->onlyOnIndex()
        ;

        yield TextField::new('menuTitleEn', 'crud.page.title_en')
            ->onlyOnIndex()
        ;

        yield DateTimeField::new('createdAt', 'crud.page.created_at')->onlyOnIndex();

        yield DateTimeField::new('updatedAt', 'crud.page.updated_at')->onlyOnIndex();

        yield CollectionField::new('translations', 'crud.page.translations')
            ->onlyOnForms()
            ->useEntryCrudForm(PageTranslationCrudController::class)
            ->allowAdd()
            ->allowDelete()
            ->renderExpanded()
            ->setEntryIsComplex()
        ;

        yield ChoiceField::new('contentTemplate', 'crud.page.content_template')
            ->setChoices([
                'default.html.twig' => 'default.html.twig',
                'technologies.html.twig' => 'technologies.html.twig',
            ])
            ->onlyOnForms()
        ;

        yield CollectionField::new('blocks', 'crud.page.blocks')
            ->onlyOnForms()
            ->useEntryCrudForm(PageBlockCrudController::class)
            ->allowAdd()
            ->allowDelete()
            ->renderExpanded()
            ->setEntryIsComplex()
        ;
    }
}
