<?php

declare(strict_types=1);

namespace App\Identity\ManageAdminUsers\UI\Admin;

use App\Identity\Shared\Infrastructure\Persistence\Doctrine\Entity\AdminUser;
use App\Shared\UI\Admin\AbstractSecuredCrudController;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * @extends AbstractSecuredCrudController<AdminUser>
 */
class AdminUserCrudController extends AbstractSecuredCrudController
{
    public function __construct(
        private readonly UserPasswordHasherInterface $userPasswordHasher,
    ) {}

    public static function getEntityFqcn(): string
    {
        return AdminUser::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->renderContentMaximized()
            ->showEntityActionsInlined()
            ->setPageTitle(Crud::PAGE_INDEX, 'crud.admin_user.entity_label.plural')
            ->setPageTitle(Crud::PAGE_DETAIL, 'crud.admin_user.entity_label.singular')
            ->setEntityLabelInPlural('crud.admin_user.entity_label.plural')
            ->setEntityLabelInSingular('crud.admin_user.entity_label.singular')
            ->setFormOptions([
                'validation_groups' => ['Default', 'admin:create'],
            ], [
                'validation_groups' => function (FormInterface $form): array {
                    /** @var null|AdminUser $admin */
                    $admin = $form->getData();
                    $groups = ['Default'];

                    if (!$admin instanceof AdminUser) {
                        return $groups;
                    }

                    if ($admin->getPlainPassword() !== null && $admin->getPlainPassword() !== '') {
                        $groups[] = 'admin:update_password';
                    }

                    return $groups;
                },
            ])
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('username', 'crud.admin_user.username');

        yield EmailField::new('email', 'crud.admin_user.email');

        yield BooleanField::new('enabled', 'crud.admin_user.enabled');

        yield ChoiceField::new('roles', 'crud.admin_user.roles')
            ->setChoices(AdminUserRoleLabels::choices())
            ->allowMultipleChoices()
            ->renderExpanded()
        ;

        yield DateTimeField::new('createdAt', 'crud.admin_user.created_at')->onlyOnIndex();

        yield DateTimeField::new('updatedAt', 'crud.admin_user.updated_at')->onlyOnIndex();

        yield DateTimeField::new('deletedAt', 'crud.admin_user.deleted_at')->onlyOnIndex();

        if ($pageName === Crud::PAGE_NEW) {
            yield TextField::new('plainPassword', 'crud.admin_user.plain_password')
                ->setFormType(PasswordType::class)
                ->setRequired(true)
                ->onlyOnForms()
                ->setFormTypeOptions([
                    'mapped' => true,
                    'empty_data' => '',
                ])
            ;
        }

        if ($pageName === Crud::PAGE_EDIT) {
            yield TextField::new('plainPassword', 'crud.admin_user.plain_password')
                ->setFormType(PasswordType::class)
                ->setRequired(false)
                ->onlyOnForms()
                ->setHelp('crud.admin_user.plain_password_help')
                ->setFormTypeOptions([
                    'mapped' => true,
                    'empty_data' => '',
                    'required' => false,
                ])
            ;
        }
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        return parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters)
            ->andWhere('entity.deletedAt IS NULL')
        ;
    }

    public function persistEntity(EntityManagerInterface $entityManager, object $entityInstance): void
    {
        $this->hashPasswordIfNeeded($entityInstance);

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->hashPasswordIfNeeded($entityInstance);
        $entityInstance->touch();

        parent::updateEntity($entityManager, $entityInstance);
    }

    public function deleteEntity(EntityManagerInterface $entityManager, object $entityInstance): void
    {
        $entityInstance->softDelete();

        $entityManager->persist($entityInstance);
        $entityManager->flush();
    }

    private function hashPasswordIfNeeded(AdminUser $admin): void
    {
        $plainPassword = $admin->getPlainPassword();

        if ($plainPassword === null || $plainPassword === '') {
            return;
        }

        $hashedPassword = $this->userPasswordHasher->hashPassword(
            $admin,
            $plainPassword
        );

        $admin->setPassword($hashedPassword);
        $admin->eraseCredentials();
    }
}
