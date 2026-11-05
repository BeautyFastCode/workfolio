<?php

declare(strict_types=1);

namespace App\CallToAction\ManageCtaMessages\UI\Admin;

use App\CallToAction\Shared\Infrastructure\Persistence\Doctrine\Entity\CtaMessage;
use App\Shared\UI\Admin\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * @extends AbstractCrudController<CtaMessage>
 */
class CtaMessageCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return CtaMessage::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->renderContentMaximized()
            ->showEntityActionsInlined()
            ->setEntityLabelInPlural('crud.cta_message.entity_label.plural')
            ->setEntityLabelInSingular('crud.cta_message.entity_label.singular')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('messagePl', 'crud.cta_message.message_pl')
            ->onlyOnIndex()
        ;

        yield TextField::new('messageEn', 'crud.cta_message.message_en')
            ->onlyOnIndex()
        ;

        yield DateTimeField::new('createdAt', 'crud.cta_message.created_at')->onlyOnIndex();

        yield DateTimeField::new('updatedAt', 'crud.cta_message.updated_at')->onlyOnIndex();

        yield CollectionField::new('translations', 'crud.cta_message.translations')
            ->onlyOnForms()
            ->useEntryCrudForm(CtaMessageTranslationCrudController::class)
            ->allowAdd()
            ->allowDelete()
            ->renderExpanded()
            ->setEntryIsComplex()
        ;
    }
}
