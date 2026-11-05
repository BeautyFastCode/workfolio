<?php

declare(strict_types=1);

namespace App\Tests\Content\ManageBlocks\UI\Admin;

use App\Content\ManageBlocks\UI\Admin\BlockCrudController;
use App\Identity\Shared\Infrastructure\Persistence\Doctrine\Entity\AdminUser;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * @internal
 *
 * @coversNothing
 */
final class BlockCrudControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->client->disableReboot();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $this->entityManager = $entityManager;

        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        new SchemaTool($this->entityManager)->createSchema($metadata);
    }

    public function test_routes_keep_their_names_paths_and_methods(): void
    {
        $router = self::getContainer()->get(RouterInterface::class);
        self::assertInstanceOf(RouterInterface::class, $router);

        $routes = $router->getRouteCollection();
        $expectedRoutes = [
            'index' => ['/admin/{_locale}/block', ['GET']],
            'new' => ['/admin/{_locale}/block/new', ['GET', 'POST']],
            'batch_delete' => ['/admin/{_locale}/block/batch-delete', ['POST']],
            'autocomplete' => ['/admin/{_locale}/block/autocomplete', ['GET']],
            'render_filters' => ['/admin/{_locale}/block/render-filters', ['GET']],
            'edit' => ['/admin/{_locale}/block/{entityId}/edit', ['GET', 'POST', 'PATCH']],
            'delete' => ['/admin/{_locale}/block/{entityId}/delete', ['POST']],
            'detail' => ['/admin/{_locale}/block/{entityId}', ['GET']],
        ];

        foreach ($expectedRoutes as $action => [$path, $methods]) {
            $route = $routes->get('admin_block_'.$action);

            self::assertNotNull($route);
            self::assertSame($path, $route->getPath());
            self::assertSame($methods, $route->getMethods());
            self::assertSame(
                BlockCrudController::class.'::'.self::controllerMethod($action),
                $route->getDefault('_controller'),
            );
        }
    }

    public function test_anonymous_user_is_redirected_to_login(): void
    {
        $this->client->request('GET', '/admin/en/block');

        self::assertResponseRedirects('/admin/en/login');
    }

    public function test_administrator_can_open_the_block_and_collection_forms(): void
    {
        $administrator = new AdminUser();
        $administrator->setEmail('admin@example.com');
        $administrator->setUsername('admin');
        $administrator->setRoles(['ROLE_ADMIN']);
        $administrator->setEnabled(true);

        $passwordHasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        self::assertInstanceOf(UserPasswordHasherInterface::class, $passwordHasher);
        $administrator->setPassword($passwordHasher->hashPassword($administrator, 'Characteristic123!'));

        $this->entityManager->persist($administrator);
        $this->entityManager->flush();
        $this->client->loginUser($administrator, 'admin');

        $this->client->request('GET', '/admin/en/block/new');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form[name="Block"]');
        self::assertSelectorExists('[name="Block[position]"]');
        self::assertSelectorExists('[name="Block[name]"]');
        self::assertSelectorExists('[name="Block[type]"]');
        self::assertSelectorExists('.field-collection-add-button');

        $this->client->request('GET', '/admin/en/block-collection/new');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form[name="BlockCollection"]');
        self::assertSelectorExists('.field-collection-add-button');
    }

    private static function controllerMethod(string $action): string
    {
        return match ($action) {
            'batch_delete' => 'batchDelete',
            'render_filters' => 'renderFilters',
            default => $action,
        };
    }
}
