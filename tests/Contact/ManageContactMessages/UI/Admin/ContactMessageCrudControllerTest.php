<?php

declare(strict_types=1);

namespace App\Tests\Contact\ManageContactMessages\UI\Admin;

use App\Contact\ManageContactMessages\UI\Admin\ContactMessageCrudController;
use App\Contact\Shared\Infrastructure\Persistence\Doctrine\Entity\ContactMessage;
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
final class ContactMessageCrudControllerTest extends WebTestCase
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
            'index' => ['/admin/{_locale}/contact-message', ['GET']],
            'new' => ['/admin/{_locale}/contact-message/new', ['GET', 'POST']],
            'batch_delete' => ['/admin/{_locale}/contact-message/batch-delete', ['POST']],
            'autocomplete' => ['/admin/{_locale}/contact-message/autocomplete', ['GET']],
            'render_filters' => ['/admin/{_locale}/contact-message/render-filters', ['GET']],
            'edit' => ['/admin/{_locale}/contact-message/{entityId}/edit', ['GET', 'POST', 'PATCH']],
            'delete' => ['/admin/{_locale}/contact-message/{entityId}/delete', ['POST']],
            'detail' => ['/admin/{_locale}/contact-message/{entityId}', ['GET']],
        ];

        foreach ($expectedRoutes as $action => [$path, $methods]) {
            $route = $routes->get('admin_contact_message_'.$action);

            self::assertNotNull($route);
            self::assertSame($path, $route->getPath());
            self::assertSame($methods, $route->getMethods());
            self::assertSame(ContactMessageCrudController::class.'::'.self::controllerMethod($action), $route->getDefault('_controller'));
        }
    }

    public function test_anonymous_user_is_redirected_to_login(): void
    {
        $this->client->request('GET', '/admin/en/contact-message');

        self::assertResponseRedirects('/admin/en/login');
    }

    public function test_administrator_can_list_and_read_a_message_but_cannot_create_or_edit_it(): void
    {
        $administrator = $this->createAdministrator();
        $message = new ContactMessage();
        $message->setFullName('Ada Reader');
        $message->setEmail('ada@example.com');
        $message->setSubject('Portfolio question');
        $message->setMessage('Please tell me more about the project.');

        $this->entityManager->persist($message);
        $this->entityManager->flush();
        $this->client->loginUser($administrator, 'admin');

        $this->client->request('GET', '/admin/en/contact-message');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Ada Reader');
        self::assertSelectorTextContains('body', 'ada@example.com');
        self::assertSelectorNotExists('.action-new');
        self::assertSelectorNotExists('.action-edit');

        $this->client->request('GET', '/admin/en/contact-message/'.$message->getId());

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Portfolio question');
        self::assertSelectorTextContains('body', 'Please tell me more about the project.');
    }

    private function createAdministrator(): AdminUser
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

        return $administrator;
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
