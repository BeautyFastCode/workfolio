<?php

declare(strict_types=1);

namespace App\Tests\Content\ManagePages\UI\Admin;

use App\Content\ManagePages\UI\Admin\PageCrudController;
use App\Content\Shared\Infrastructure\Persistence\Doctrine\Entity\Page;
use App\Identity\Shared\Infrastructure\Persistence\Doctrine\Entity\AdminUser;
use App\Shared\Domain\LocaleEnum;
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
final class PageCrudControllerTest extends WebTestCase
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
            'index' => ['/admin/{_locale}/page', ['GET']],
            'new' => ['/admin/{_locale}/page/new', ['GET', 'POST']],
            'batch_delete' => ['/admin/{_locale}/page/batch-delete', ['POST']],
            'autocomplete' => ['/admin/{_locale}/page/autocomplete', ['GET']],
            'render_filters' => ['/admin/{_locale}/page/render-filters', ['GET']],
            'edit' => ['/admin/{_locale}/page/{entityId}/edit', ['GET', 'POST', 'PATCH']],
            'delete' => ['/admin/{_locale}/page/{entityId}/delete', ['POST']],
            'detail' => ['/admin/{_locale}/page/{entityId}', ['GET']],
        ];

        foreach ($expectedRoutes as $action => [$path, $methods]) {
            $route = $routes->get('admin_page_'.$action);

            self::assertNotNull($route);
            self::assertSame($path, $route->getPath());
            self::assertSame($methods, $route->getMethods());
            self::assertSame(
                PageCrudController::class.'::'.self::controllerMethod($action),
                $route->getDefault('_controller'),
            );
        }
    }

    public function test_anonymous_user_is_redirected_to_login(): void
    {
        $this->client->request('GET', '/admin/en/page');

        self::assertResponseRedirects('/admin/en/login');
    }

    public function test_administrator_can_use_the_menu_and_page_form(): void
    {
        $this->loginAdministrator();

        $this->client->request('GET', '/admin/en');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('#main-menu', 'Pages');

        $this->client->request('GET', '/admin/en/page/new');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form[name="Page"]');
        self::assertSelectorExists('[name="Page[position]"]');
        self::assertSelectorExists('[name="Page[published]"]');
        self::assertSelectorExists('[name="Page[contentTemplate]"]');
        self::assertSelectorCount(2, '.field-collection-add-button');
    }

    public function test_administrator_can_create_and_edit_a_page_with_a_translation(): void
    {
        $this->loginAdministrator();
        $crawler = $this->client->request('GET', '/admin/en/page/new');
        $values = $crawler->filter('form[name="Page"]')->form()->getPhpValues();
        self::assertIsArray($values['Page']);
        $values['Page'] = array_replace($values['Page'], [
            'position' => '7',
            'contentTemplate' => 'default.html.twig',
            'published' => '1',
            'translations' => [['locale' => 'en', 'title' => 'Created page', 'slug' => 'created-page']],
        ]);

        $this->client->request('POST', '/admin/en/page/new', $values);

        self::assertResponseRedirects();
        $this->entityManager->clear();
        $entity = $this->entityManager->getRepository(Page::class)->findOneBy(['position' => 7]);
        self::assertInstanceOf(Page::class, $entity);
        self::assertSame('Created page', $entity->getTitle(LocaleEnum::EN));
        self::assertCount(1, $entity->getTranslations());
        $id = $entity->getId();

        $crawler = $this->client->request('GET', '/admin/en/page/'.$id.'/edit');
        $form = $crawler->filter('form[name="Page"]')->form([
            'Page[position]' => '9',
            'Page[translations][0][title]' => 'Updated page',
        ]);
        $this->client->submit($form);

        self::assertResponseRedirects();
        $this->entityManager->clear();
        $updated = $this->entityManager->find(Page::class, $id);
        self::assertInstanceOf(Page::class, $updated);
        self::assertSame(9, $updated->getPosition());
        self::assertSame('Updated page', $updated->getTitle(LocaleEnum::EN));
        self::assertCount(1, $updated->getTranslations());
    }

    public function test_invalid_page_form_does_not_persist_an_entity(): void
    {
        $this->loginAdministrator();
        $crawler = $this->client->request('GET', '/admin/en/page/new');
        $form = $crawler->filter('form[name="Page"]')->form();
        $this->client->submit($form);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorExists('.invalid-feedback');
        $this->entityManager->clear();
        self::assertSame(0, $this->entityManager->getRepository(Page::class)->count([]));
    }

    private function loginAdministrator(): void
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
