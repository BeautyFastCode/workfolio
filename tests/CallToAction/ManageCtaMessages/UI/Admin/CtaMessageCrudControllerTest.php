<?php

declare(strict_types=1);

namespace App\Tests\CallToAction\ManageCtaMessages\UI\Admin;

use App\CallToAction\ManageCtaMessages\UI\Admin\CtaMessageCrudController;
use App\CallToAction\ManageCtaMessages\UI\Admin\CtaMessageTranslationCrudController;
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
final class CtaMessageCrudControllerTest extends WebTestCase
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

        $this->assertCrudRoutes(
            $router,
            'cta_message',
            'cta-message',
            CtaMessageCrudController::class,
        );
        $this->assertCrudRoutes(
            $router,
            'cta_message_translation',
            'cta-message-translation',
            CtaMessageTranslationCrudController::class,
        );
    }

    public function test_anonymous_user_is_redirected_to_login(): void
    {
        $this->client->request('GET', '/admin/en/cta-message');

        self::assertResponseRedirects('/admin/en/login');
    }

    public function test_administrator_can_open_the_translation_forms(): void
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

        $this->client->request('GET', '/admin/en/cta-message/new');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form[name="CtaMessage"]');
        self::assertSelectorExists('.field-collection');
        self::assertSelectorExists('.field-collection-add-button');

        $this->client->request('GET', '/admin/en/cta-message-translation/new');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form[name="CtaMessageTranslation"]');
        self::assertSelectorExists('[name="CtaMessageTranslation[locale]"]');
        self::assertSelectorExists('[name="CtaMessageTranslation[message]"]');
    }

    private function assertCrudRoutes(
        RouterInterface $router,
        string $routeSegment,
        string $pathSegment,
        string $controller,
    ): void {
        $routes = $router->getRouteCollection();
        $expectedRoutes = [
            'index' => [sprintf('/admin/{_locale}/%s', $pathSegment), ['GET']],
            'new' => [sprintf('/admin/{_locale}/%s/new', $pathSegment), ['GET', 'POST']],
            'batch_delete' => [sprintf('/admin/{_locale}/%s/batch-delete', $pathSegment), ['POST']],
            'autocomplete' => [sprintf('/admin/{_locale}/%s/autocomplete', $pathSegment), ['GET']],
            'render_filters' => [sprintf('/admin/{_locale}/%s/render-filters', $pathSegment), ['GET']],
            'edit' => [sprintf('/admin/{_locale}/%s/{entityId}/edit', $pathSegment), ['GET', 'POST', 'PATCH']],
            'delete' => [sprintf('/admin/{_locale}/%s/{entityId}/delete', $pathSegment), ['POST']],
            'detail' => [sprintf('/admin/{_locale}/%s/{entityId}', $pathSegment), ['GET']],
        ];

        foreach ($expectedRoutes as $action => [$path, $methods]) {
            $route = $routes->get('admin_'.$routeSegment.'_'.$action);

            self::assertNotNull($route);
            self::assertSame($path, $route->getPath());
            self::assertSame($methods, $route->getMethods());
            self::assertSame($controller.'::'.self::controllerMethod($action), $route->getDefault('_controller'));
        }
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
