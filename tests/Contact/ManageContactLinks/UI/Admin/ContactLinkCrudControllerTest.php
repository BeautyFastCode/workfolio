<?php

declare(strict_types=1);

namespace App\Tests\Contact\ManageContactLinks\UI\Admin;

use App\Contact\ManageContactLinks\UI\Admin\ContactLinkCrudController;
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
final class ContactLinkCrudControllerTest extends WebTestCase
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
            'index' => ['/admin/{_locale}/contact-link', ['GET']],
            'new' => ['/admin/{_locale}/contact-link/new', ['GET', 'POST']],
            'batch_delete' => ['/admin/{_locale}/contact-link/batch-delete', ['POST']],
            'autocomplete' => ['/admin/{_locale}/contact-link/autocomplete', ['GET']],
            'render_filters' => ['/admin/{_locale}/contact-link/render-filters', ['GET']],
            'edit' => ['/admin/{_locale}/contact-link/{entityId}/edit', ['GET', 'POST', 'PATCH']],
            'delete' => ['/admin/{_locale}/contact-link/{entityId}/delete', ['POST']],
            'detail' => ['/admin/{_locale}/contact-link/{entityId}', ['GET']],
        ];

        foreach ($expectedRoutes as $action => [$path, $methods]) {
            $route = $routes->get('admin_contact_link_'.$action);

            self::assertNotNull($route);
            self::assertSame($path, $route->getPath());
            self::assertSame($methods, $route->getMethods());
            self::assertSame(ContactLinkCrudController::class.'::'.self::controllerMethod($action), $route->getDefault('_controller'));
        }
    }

    public function test_anonymous_user_is_redirected_to_login(): void
    {
        $this->client->request('GET', '/admin/en/contact-link');

        self::assertResponseRedirects('/admin/en/login');
    }

    public function test_administrator_can_open_the_contact_link_form(): void
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

        $this->client->request('GET', '/admin/en/contact-link/new');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form[name="ContactLink"]');
        self::assertSelectorExists('[name="ContactLink[name]"]');
        self::assertSelectorExists('[name="ContactLink[enabled]"]');
        self::assertSelectorExists('[name="ContactLink[position]"]');
        self::assertSelectorExists('[name="ContactLink[icon]"]');
        self::assertSelectorExists('[name="ContactLink[href]"]');
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
