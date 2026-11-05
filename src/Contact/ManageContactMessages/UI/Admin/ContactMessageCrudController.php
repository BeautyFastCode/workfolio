<?php

declare(strict_types=1);

namespace App\Contact\ManageContactMessages\UI\Admin;

use App\Contact\Shared\Infrastructure\Persistence\Doctrine\Entity\ContactMessage;
use App\Shared\UI\Admin\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;

/**
 * @extends AbstractCrudController<ContactMessage>
 */
class ContactMessageCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ContactMessage::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->renderContentMaximized()
            ->showEntityActionsInlined()
            ->setPageTitle(Crud::PAGE_INDEX, 'crud.contact_message.entity_label.plural')
            ->setPageTitle(Crud::PAGE_DETAIL, 'crud.contact_message.entity_label.singular')
            ->setEntityLabelInPlural('crud.contact_message.entity_label.plural')
            ->setEntityLabelInSingular('crud.contact_message.entity_label.singular')
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::EDIT)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('fullName', 'crud.contact_message.full_name');

        yield EmailField::new('email', 'crud.contact_message.email');

        yield TextField::new('subject', 'crud.contact_message.subject');

        yield TextField::new('message', 'crud.contact_message.message');

        yield DateTimeField::new('createdAt', 'crud.contact_message.created_at');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('fullName'))
            ->add(TextFilter::new('email'))
            ->add(TextFilter::new('subject'))
            ->add(TextFilter::new('message'))
            ->add(DateTimeFilter::new('createdAt'))
        ;
    }
}
