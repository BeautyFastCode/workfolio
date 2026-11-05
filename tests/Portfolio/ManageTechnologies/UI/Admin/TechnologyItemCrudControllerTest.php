<?php

declare(strict_types=1);

namespace App\Tests\Portfolio\ManageTechnologies\UI\Admin;

use App\Identity\Shared\Infrastructure\Persistence\Doctrine\Entity\AdminUser;
use App\Portfolio\ManageTechnologies\UI\Admin\TechnologyItemCrudController;
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
final class TechnologyItemCrudControllerTest extends WebTestCase
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
            'index' => ['/admin/{_locale}/technology-item', ['GET']],
            'new' => ['/admin/{_locale}/technology-item/new', ['GET', 'POST']],
            'batch_delete' => ['/admin/{_locale}/technology-item/batch-delete', ['POST']],
            'autocomplete' => ['/admin/{_locale}/technology-item/autocomplete', ['GET']],
            'render_filters' => ['/admin/{_locale}/technology-item/render-filters', ['GET']],
            'edit' => ['/admin/{_locale}/technology-item/{entityId}/edit', ['GET', 'POST', 'PATCH']],
            'delete' => ['/admin/{_locale}/technology-item/{entityId}/delete', ['POST']],
            'detail' => ['/admin/{_locale}/technology-item/{entityId}', ['GET']],
        ];

        foreach ($expectedRoutes as $action => [$path, $methods]) {
            $route = $routes->get('admin_technology_item_'.$action);

            self::assertNotNull($route);
            self::assertSame($path, $route->getPath());
            self::assertSame($methods, $route->getMethods());
            self::assertSame(TechnologyItemCrudController::class.'::'.self::controllerMethod($action), $route->getDefault('_controller'));
        }
    }

    public function test_anonymous_user_is_redirected_to_login(): void
    {
        $this->client->request('GET', '/admin/en/technology-item');

        self::assertResponseRedirects('/admin/en/login');
    }

    public function test_administrator_can_open_the_item_and_group_forms(): void
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

        $this->client->request('GET', '/admin/en/technology-item/new');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form[name="TechnologyItem"]');
        self::assertSelectorExists('[name="TechnologyItem[name]"]');
        self::assertSelectorExists('[name="TechnologyItem[position]"]');
        self::assertSelectorExists('[name="TechnologyItem[icon]"]');

        $this->client->request('GET', '/admin/en/technology-group/new');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form[name="TechnologyGroup"]');
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
