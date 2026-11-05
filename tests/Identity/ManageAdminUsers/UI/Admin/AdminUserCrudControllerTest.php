<?php

declare(strict_types=1);

namespace App\Tests\Identity\ManageAdminUsers\UI\Admin;

use App\Identity\ManageAdminUsers\UI\Admin\AdminUserCrudController;
use App\Identity\Shared\Infrastructure\Persistence\Doctrine\Entity\AdminUser;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Orm\EntityRepositoryInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * @internal
 *
 * @covers \App\Identity\ManageAdminUsers\UI\Admin\AdminUserCrudController
 */
final class AdminUserCrudControllerTest extends TestCase
{
    public function test_crud_configures_layout_and_password_validation_groups(): void
    {
        $controller = $this->controller();
        $crud = $controller->configureCrud(Crud::new())->getAsDto();

        self::assertSame(AdminUser::class, $controller::getEntityFqcn());
        self::assertSame(Crud::LAYOUT_CONTENT_FULL, $crud->getContentWidth());
        self::assertFalse($crud->showEntityActionsAsDropdown());
        self::assertSame(
            ['Default', 'admin:create'],
            $crud->getNewFormOptions()->get('validation_groups'),
        );

        $validationGroups = $crud->getEditFormOptions()->get('validation_groups');
        self::assertIsCallable($validationGroups);

        $user = new AdminUser();
        $form = $this->createStub(FormInterface::class);
        $form->method('getData')->willReturn($user);

        self::assertSame(['Default'], $validationGroups($form));

        $user->setPlainPassword('Changed123!');

        self::assertSame(['Default', 'admin:update_password'], $validationGroups($form));
    }

    public function test_fields_configure_visibility_and_password_options(): void
    {
        $controller = $this->controller();
        $indexFields = $this->fieldsByProperty($controller, Crud::PAGE_INDEX);
        $newFields = $this->fieldsByProperty($controller, Crud::PAGE_NEW);
        $editFields = $this->fieldsByProperty($controller, Crud::PAGE_EDIT);

        self::assertSame(
            ['username', 'email', 'enabled', 'roles', 'createdAt', 'updatedAt', 'deletedAt'],
            array_keys($indexFields),
        );
        self::assertSame(
            ['username', 'email', 'enabled', 'roles', 'createdAt', 'updatedAt', 'deletedAt', 'plainPassword'],
            array_keys($newFields),
        );
        self::assertSame(array_keys($newFields), array_keys($editFields));

        self::assertSame(PasswordType::class, $newFields['plainPassword']->getFormType());
        self::assertTrue($newFields['plainPassword']->getFormTypeOption('required'));
        self::assertSame('', $newFields['plainPassword']->getFormTypeOption('empty_data'));

        self::assertSame(PasswordType::class, $editFields['plainPassword']->getFormType());
        self::assertFalse($editFields['plainPassword']->getFormTypeOption('required'));
        self::assertSame('', $editFields['plainPassword']->getFormTypeOption('empty_data'));
        self::assertSame('crud.admin_user.plain_password_help', $editFields['plainPassword']->getHelp());
    }

    public function test_index_query_excludes_soft_deleted_users(): void
    {
        $controller = $this->controller();
        $search = new SearchDto(new Request(), null, null, [], [], null);
        $entity = new EntityDto(AdminUser::class, new ClassMetadata(AdminUser::class));
        $fields = new FieldCollection([]);
        $filters = new FilterCollection();

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder
            ->expects($this->once())
            ->method('andWhere')
            ->with('entity.deletedAt IS NULL')
            ->willReturnSelf()
        ;

        $repository = $this->createMock(EntityRepositoryInterface::class);
        $repository
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->with($search, $entity, $fields, $filters)
            ->willReturn($queryBuilder)
        ;

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('get')
            ->with(EntityRepositoryInterface::class)
            ->willReturn($repository)
        ;
        $controller->setContainer($container);

        self::assertSame(
            $queryBuilder,
            $controller->createIndexQueryBuilder($search, $entity, $fields, $filters),
        );
    }

    public function test_persist_hashes_the_password_clears_credentials_and_saves_the_user(): void
    {
        $user = $this->user();
        $user->setPlainPassword('Created123!');

        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher
            ->expects($this->once())
            ->method('hashPassword')
            ->with($user, 'Created123!')
            ->willReturn('created-hash')
        ;
        $entityManager = $this->savingEntityManager($user);

        new AdminUserCrudController($hasher)->persistEntity($entityManager, $user);

        self::assertSame('created-hash', $user->getPassword());
        self::assertNull($user->getPlainPassword());
    }

    public function test_update_without_a_plain_password_keeps_the_hash_and_touches_the_user(): void
    {
        $user = $this->user();
        $user->setPassword('existing-hash');
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->expects($this->never())->method('hashPassword');
        $entityManager = $this->savingEntityManager($user);

        new AdminUserCrudController($hasher)->updateEntity($entityManager, $user);

        self::assertSame('existing-hash', $user->getPassword());
        self::assertNotNull($user->getUpdatedAt());
    }

    public function test_update_with_a_plain_password_replaces_the_hash_and_clears_credentials(): void
    {
        $user = $this->user();
        $user->setPassword('existing-hash');
        $user->setPlainPassword('Changed123!');

        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher
            ->expects($this->once())
            ->method('hashPassword')
            ->with($user, 'Changed123!')
            ->willReturn('changed-hash')
        ;
        $entityManager = $this->savingEntityManager($user);

        new AdminUserCrudController($hasher)->updateEntity($entityManager, $user);

        self::assertSame('changed-hash', $user->getPassword());
        self::assertNull($user->getPlainPassword());
        self::assertNotNull($user->getUpdatedAt());
    }

    public function test_delete_soft_deletes_anonymizes_disables_and_persists_the_user(): void
    {
        $user = $this->user();
        $entityManager = $this->savingEntityManager($user);

        $this->controller()->deleteEntity($entityManager, $user);

        self::assertMatchesRegularExpression('/^admin@example\.com__deleted_[a-z0-9]{8}$/', $user->getEmail());
        self::assertMatchesRegularExpression('/^admin__deleted_[a-z0-9]{8}$/', $user->getUsername());
        self::assertFalse($user->isEnabled());
        self::assertTrue($user->isDeleted());
        self::assertNotNull($user->getDeletedAt());
    }

    /**
     * @return array<string, FieldDto>
     */
    private function fieldsByProperty(AdminUserCrudController $controller, string $pageName): array
    {
        $fields = [];

        foreach ($controller->configureFields($pageName) as $field) {
            self::assertInstanceOf(FieldInterface::class, $field);
            $dto = $field->getAsDto();
            $fields[$dto->getProperty()] = $dto;
        }

        return $fields;
    }

    private function savingEntityManager(AdminUser $user): EntityManagerInterface
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('persist')->with($user);
        $entityManager->expects($this->once())->method('flush');
        $entityManager->expects($this->never())->method('remove');

        return $entityManager;
    }

    private function controller(): AdminUserCrudController
    {
        return new AdminUserCrudController($this->createStub(UserPasswordHasherInterface::class));
    }

    private function user(): AdminUser
    {
        $user = new AdminUser();
        $user->setEmail('admin@example.com');
        $user->setUsername('admin');
        $user->setRoles(['ROLE_ADMIN']);
        $user->setEnabled(true);

        return $user;
    }
}
