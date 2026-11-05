<?php

declare(strict_types=1);

namespace App\Tests\Content\ManagePages\UI\Admin;

use App\Content\ManagePages\UI\Admin\PageTranslationCrudController;
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
final class PageTranslationCrudControllerTest extends WebTestCase
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
            'index' => ['/admin/{_locale}/page-translation', ['GET']],
            'new' => ['/admin/{_locale}/page-translation/new', ['GET', 'POST']],
            'batch_delete' => ['/admin/{_locale}/page-translation/batch-delete', ['POST']],
            'autocomplete' => ['/admin/{_locale}/page-translation/autocomplete', ['GET']],
            'render_filters' => ['/admin/{_locale}/page-translation/render-filters', ['GET']],
            'edit' => ['/admin/{_locale}/page-translation/{entityId}/edit', ['GET', 'POST', 'PATCH']],
            'delete' => ['/admin/{_locale}/page-translation/{entityId}/delete', ['POST']],
            'detail' => ['/admin/{_locale}/page-translation/{entityId}', ['GET']],
        ];

        foreach ($expectedRoutes as $action => [$path, $methods]) {
            $route = $routes->get('admin_page_translation_'.$action);

            self::assertNotNull($route);
            self::assertSame($path, $route->getPath());
            self::assertSame($methods, $route->getMethods());
            self::assertSame(
                PageTranslationCrudController::class.'::'.self::controllerMethod($action),
                $route->getDefault('_controller'),
            );
        }
    }

    public function test_anonymous_user_is_redirected_to_login(): void
    {
        $this->client->request('GET', '/admin/en/page-translation');

        self::assertResponseRedirects('/admin/en/login');
    }

    public function test_administrator_can_open_the_translation_and_page_forms(): void
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

        $this->client->request('GET', '/admin/en/page-translation/new');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form[name="PageTranslation"]');
        self::assertSelectorExists('[name="PageTranslation[locale]"]');
        self::assertSelectorExists('[name="PageTranslation[title]"]');
        self::assertSelectorExists('[name="PageTranslation[slug]"]');
        self::assertSelectorExists('[name="PageTranslation[menuTitle]"]');

        $this->client->request('GET', '/admin/en/page/new');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form[name="Page"]');
        self::assertSelectorCount(2, '.field-collection-add-button');
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
