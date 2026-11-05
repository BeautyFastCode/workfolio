<?php

declare(strict_types=1);

namespace App\Contact\ManageContactLinks\UI\Admin;

use App\Contact\Shared\Infrastructure\Persistence\Doctrine\Entity\ContactLink;
use App\Shared\UI\Admin\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * @extends AbstractCrudController<ContactLink>
 */
class ContactLinkCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ContactLink::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->renderContentMaximized()
            ->showEntityActionsInlined()
            ->setEntityLabelInPlural('crud.contact_link.entity_label.plural')
            ->setEntityLabelInSingular('crud.contact_link.entity_label.singular')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name', 'crud.contact_link.name');

        yield BooleanField::new('enabled', 'crud.contact_link.enabled');

        yield IntegerField::new('position', 'crud.contact_link.position');

        yield TextField::new('icon', 'crud.contact_link.icon')->onlyOnForms();

        yield TextField::new('href', 'crud.contact_link.href')->onlyOnForms();

        yield DateTimeField::new('createdAt', 'crud.contact_link.created_at')->onlyOnIndex();

        yield DateTimeField::new('updatedAt', 'crud.contact_link.updated_at')->onlyOnIndex();
    }
}
