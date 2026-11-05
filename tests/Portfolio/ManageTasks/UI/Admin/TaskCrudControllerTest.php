<?php

declare(strict_types=1);

namespace App\Tests\Portfolio\ManageTasks\UI\Admin;

use App\Identity\Shared\Infrastructure\Persistence\Doctrine\Entity\AdminUser;
use App\Portfolio\ManageTasks\UI\Admin\TaskCrudController;
use App\Portfolio\ManageTasks\UI\Admin\TaskTranslationCrudController;
use App\Portfolio\Shared\Infrastructure\Persistence\Doctrine\Entity\Task;
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
final class TaskCrudControllerTest extends WebTestCase
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

        $this->assertCrudRoutes($router, 'task', 'task', TaskCrudController::class);
        $this->assertCrudRoutes(
            $router,
            'task_translation',
            'task-translation',
            TaskTranslationCrudController::class,
        );
    }

    public function test_anonymous_user_is_redirected_to_login(): void
    {
        $this->client->request('GET', '/admin/en/task');

        self::assertResponseRedirects('/admin/en/login');
    }

    public function test_administrator_can_open_the_task_and_translation_forms(): void
    {
        $this->loginAdministrator();

        $this->client->request('GET', '/admin/en/task/new');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form[name="Task"]');
        self::assertSelectorExists('[name="Task[position]"]');
        self::assertSelectorExists('input[type="file"][accept="image/jpeg,image/png,image/webp"]');
        self::assertSelectorExists('.field-collection-add-button');

        $this->client->request('GET', '/admin/en/task-translation/new');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form[name="TaskTranslation"]');
        self::assertSelectorExists('[name="TaskTranslation[locale]"]');
        self::assertSelectorExists('[name="TaskTranslation[name]"]');
        self::assertSelectorExists('[name="TaskTranslation[description]"]');
        self::assertSelectorExists('[name="TaskTranslation[tools]"]');
        self::assertSelectorExists('[name="TaskTranslation[impact]"]');
    }

    public function test_administrator_can_create_and_edit_a_task_with_a_translation(): void
    {
        $this->loginAdministrator();
        $crawler = $this->client->request('GET', '/admin/en/task/new');
        $values = $crawler->filter('form[name="Task"]')->form()->getPhpValues();
        self::assertIsArray($values['Task']);
        $values['Task'] = array_replace($values['Task'], [
            'position' => '7',
            'translations' => [['locale' => 'en', 'name' => 'Created task', 'tools' => 'PHP', 'impact' => 'A measurable improvement.']],
        ]);

        $this->client->request('POST', '/admin/en/task/new', $values);

        self::assertResponseRedirects();
        $this->entityManager->clear();
        $entity = $this->entityManager->getRepository(Task::class)->findOneBy(['position' => 7]);
        self::assertInstanceOf(Task::class, $entity);
        self::assertSame('Created task', $entity->getName(LocaleEnum::EN));
        self::assertCount(1, $entity->getTranslations());
        $id = $entity->getId();

        $crawler = $this->client->request('GET', '/admin/en/task/'.$id.'/edit');
        $form = $crawler->filter('form[name="Task"]')->form([
            'Task[position]' => '9',
            'Task[translations][0][name]' => 'Updated task',
        ]);
        $this->client->submit($form);

        self::assertResponseRedirects();
        $this->entityManager->clear();
        $updated = $this->entityManager->find(Task::class, $id);
        self::assertInstanceOf(Task::class, $updated);
        self::assertSame(9, $updated->getPosition());
        self::assertSame('Updated task', $updated->getName(LocaleEnum::EN));
        self::assertCount(1, $updated->getTranslations());
    }

    public function test_invalid_task_form_does_not_persist_an_entity(): void
    {
        $this->loginAdministrator();
        $crawler = $this->client->request('GET', '/admin/en/task/new');
        $form = $crawler->filter('form[name="Task"]')->form();
        $this->client->submit($form);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorExists('.invalid-feedback');
        $this->entityManager->clear();
        self::assertSame(0, $this->entityManager->getRepository(Task::class)->count([]));
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
