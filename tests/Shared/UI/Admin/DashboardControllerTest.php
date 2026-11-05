<?php

declare(strict_types=1);

namespace App\Tests\Shared\UI\Admin;

use App\Identity\Shared\Infrastructure\Persistence\Doctrine\Entity\AdminUser;
use App\Shared\UI\Admin\DashboardController;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Dto\LocaleDto;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * @internal
 *
 * @coversNothing
 */
final class DashboardControllerTest extends WebTestCase
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

    public function test_route_keeps_its_name_path_methods_and_controller(): void
    {
        $router = self::getContainer()->get(RouterInterface::class);
        self::assertInstanceOf(RouterInterface::class, $router);

        $route = $router->getRouteCollection()->get('admin');

        self::assertNotNull($route);
        self::assertSame('/admin/{_locale}', $route->getPath());
        self::assertSame([], $route->getMethods());
        self::assertSame(DashboardController::class.'::index', $route->getDefault('_controller'));
        self::assertSame(DashboardController::class, $route->getDefault('dashboardControllerFqcn'));
    }

    public function test_dashboard_configures_title_and_translation_domain(): void
    {
        $dashboard = new DashboardController()->configureDashboard()->getAsDto();

        self::assertSame('icons/favicon.svg', $dashboard->getFaviconPath());
        self::assertSame('admin', $dashboard->getTranslationDomain());
        self::assertStringContainsString('Workfolio', $dashboard->getTitle());

        $locales = $dashboard->getLocales();

        self::assertCount(2, $locales);
        self::assertSame(['pl', 'en'], array_map(static fn (LocaleDto $locale): string => $locale->getLocale(), $locales));
        self::assertSame(['🇵🇱 Polski', '🇬🇧 English'], array_map(static fn (LocaleDto $locale): string => $locale->getName(), $locales));
        self::assertSame(Crud::LAYOUT_CONTENT_FULL, $dashboard->getContentWidth());
    }

    public function test_anonymous_user_is_redirected_to_login(): void
    {
        $this->client->request('GET', '/admin/en');

        self::assertResponseRedirects('/admin/en/login');
    }

    public function test_administrator_can_open_the_dashboard_and_menu(): void
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

        $this->client->request('GET', '/admin/en');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('title', 'Dashboard');
        self::assertSelectorTextContains('#main-menu', 'Pages');
        self::assertSelectorTextContains('#main-menu', 'Blocks collections');
        self::assertSelectorTextContains('#main-menu', 'Contact links');
        self::assertSelectorTextContains('#main-menu', 'CTA messages');
        self::assertSelectorTextContains('#main-menu', 'Technologies');
        self::assertSelectorTextContains('#main-menu', 'Statistics');
        self::assertSelectorTextContains('#main-menu', 'Tasks');
        self::assertSelectorTextContains('#main-menu', 'Contact messages');
        self::assertSelectorTextContains('#main-menu', 'Administrators');
    }
}
