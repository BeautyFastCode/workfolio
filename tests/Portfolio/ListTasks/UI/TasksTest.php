<?php

declare(strict_types=1);

namespace App\Tests\Portfolio\ListTasks\UI;

use App\Content\Shared\Infrastructure\Persistence\Doctrine\Entity\BlockCollection;
use App\Content\Shared\Infrastructure\Persistence\Doctrine\Entity\Page;
use App\Content\Shared\Infrastructure\Persistence\Doctrine\Entity\PageBlock;
use App\Content\Shared\Infrastructure\Persistence\Doctrine\Entity\PageBlockTranslation;
use App\Content\Shared\Infrastructure\Persistence\Doctrine\Entity\PageTranslation;
use App\Content\Shared\Infrastructure\Persistence\Doctrine\Enum\BlockType;
use App\Portfolio\ListTasks\Application\ListTasksHandler;
use App\Portfolio\ListTasks\Application\TaskFinder;
use App\Portfolio\ListTasks\Application\TaskView;
use App\Portfolio\ListTasks\UI\Tasks;
use App\Portfolio\Shared\Infrastructure\Persistence\Doctrine\Entity\Task;
use App\Portfolio\Shared\Infrastructure\Persistence\Doctrine\Entity\TaskTranslation;
use App\Shared\Domain\LocaleEnum;
use App\Shared\UI\TurboFrame;
use App\Shared\UI\Twig\LocaleProvider;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bridge\Doctrine\DataCollector\DoctrineDataCollector;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Profiler\Profile;

/**
 * @internal
 *
 * @covers \App\Portfolio\ListTasks\UI\Tasks
 */
final class TasksTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = self::createClient(['debug' => true]);
        $this->client->disableReboot();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $this->entityManager = $entityManager;
        new SchemaTool($entityManager)->createSchema($entityManager->getMetadataFactory()->getAllMetadata());

        foreach (['header', 'footer'] as $name) {
            $collection = new BlockCollection();
            $collection->setName($name);
            $entityManager->persist($collection);
        }

        $entityManager->flush();
    }

    public function test_component_is_a_thin_adapter_from_current_locale_to_the_handler(): void
    {
        $items = [new TaskView(null, 'Zadanie', null, 'PHP', 'Wpływ')];
        $finder = $this->createMock(TaskFinder::class);
        $finder
            ->expects(self::once())
            ->method('findAll')
            ->with('pl')
            ->willReturn($items)
        ;
        $requestStack = new RequestStack();
        $request = Request::create('/pl');
        $request->setLocale('pl');
        $requestStack->push($request);
        $component = new Tasks(
            new ListTasksHandler($finder),
            new LocaleProvider($requestStack),
        );

        $component->postMount();

        self::assertSame($items, $component->data);
    }

    public function test_rendered_tasks_preserve_order_locales_thumbnail_fallback_and_labels(): void
    {
        $this->persistTasksPage();
        $this->persistTask(2, 'second.webp', ['Second', 'Second description', 'PHP', 'Second impact'], ['Drugi', 'Drugi opis', 'PHP', 'Drugi wpływ']);
        $this->persistTask(1, null, ['First', 'First description', 'SQL', 'First impact'], ['Pierwszy', 'Pierwszy opis', 'SQL', 'Pierwszy wpływ']);

        $this->requestTasksPage('/en/tasks-page');

        self::assertResponseIsSuccessful();
        self::assertSelectorCount(2, '#turbo_frame_page_content .row.g-4 h3');
        self::assertSelectorTextSame('#turbo_frame_page_content .row.g-4 > .col-md-9:nth-child(2) h3', 'First');
        self::assertSelectorTextSame('#turbo_frame_page_content .row.g-4 > .col-md-9:nth-child(4) h3', 'Second');
        self::assertSelectorExists('#turbo_frame_page_content .row.g-4 .image-x-fallback');
        self::assertSelectorExists('#turbo_frame_page_content .row.g-4 img[src="/uploads/images/second.webp"][alt="Second"]');
        self::assertSelectorTextContains('#turbo_frame_page_content .row.g-4', 'Tools: SQL');
        self::assertSelectorTextContains('#turbo_frame_page_content .row.g-4', 'Measurable Impact: First impact');

        $this->requestTasksPage('/pl/tasks-page');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextSame('#turbo_frame_page_content .row.g-4 > .col-md-9:nth-child(2) h3', 'Pierwszy');
        self::assertSelectorTextSame('#turbo_frame_page_content .row.g-4 > .col-md-9:nth-child(4) h3', 'Drugi');
        self::assertSelectorExists('#turbo_frame_page_content .row.g-4 img[src="/uploads/images/second.webp"][alt="Drugi"]');
        self::assertSelectorTextContains('#turbo_frame_page_content .row.g-4', 'Narzędzia: SQL');
        self::assertSelectorTextContains('#turbo_frame_page_content .row.g-4', 'Mierzalny wpływ: Pierwszy wpływ');
    }

    public function test_an_empty_task_list_renders_the_existing_empty_container(): void
    {
        $this->persistTasksPage();

        $this->requestTasksPage('/en/tasks-page');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('#turbo_frame_page_content .row.g-4');
        self::assertSelectorCount(0, '#turbo_frame_page_content .row.g-4 h3');
    }

    public function test_full_rendering_executes_one_shaped_task_query(): void
    {
        $this->persistTasksPage();
        $this->persistTask(1, null, ['Task', null, 'PHP', 'Impact'], ['Zadanie', null, 'PHP', 'Wpływ']);
        $this->client->enableProfiler();

        $this->requestTasksPage('/en/tasks-page');

        self::assertResponseIsSuccessful();
        $profile = $this->client->getProfile();
        self::assertInstanceOf(Profile::class, $profile);
        $collector = $profile->getCollector('db');
        self::assertInstanceOf(DoctrineDataCollector::class, $collector);

        /** @var array<string, list<array{sql: string}>> $queryData */
        $queryData = $collector->getQueries();
        $queries = [];

        foreach ($queryData as $connectionQueries) {
            array_push($queries, ...$connectionQueries);
        }

        $taskQueries = array_values(array_filter(
            $queries,
            static fn (array $query): bool => str_contains($query['sql'], 'FROM app_task '),
        ));

        self::assertCount(1, $taskQueries);
        self::assertStringContainsString('LEFT JOIN app_task_translation ', $taskQueries[0]['sql']);
        self::assertStringContainsString('locale', $taskQueries[0]['sql']);
        self::assertStringContainsString('ORDER BY', $taskQueries[0]['sql']);
        self::assertStringContainsString('position', $taskQueries[0]['sql']);
    }

    private function persistTasksPage(): void
    {
        $page = new Page();
        $page->setPosition(1);
        $page->setPublished(true);
        $page->setContentTemplate('default.html.twig');

        $english = new PageTranslation();
        $english->setLocale(LocaleEnum::EN);
        $english->setSlug('tasks-page');
        $english->setTitle('Tasks');
        $page->addTranslation($english);

        $polish = new PageTranslation();
        $polish->setLocale(LocaleEnum::PL);
        $polish->setSlug('zadania-page');
        $polish->setTitle('Zadania');
        $page->addTranslation($polish);

        $block = new PageBlock();
        $block->setName('tasks');
        $block->setType(BlockType::TWIG);
        $block->setPosition(1);

        foreach ([LocaleEnum::EN, LocaleEnum::PL] as $locale) {
            $translation = new PageBlockTranslation();
            $translation->setLocale($locale);
            $translation->setContent('[[Tasks]]');
            $block->addTranslation($translation);
        }

        $page->addBlock($block);
        $this->entityManager->persist($page);
        $this->entityManager->flush();
    }

    private function requestTasksPage(string $uri): void
    {
        $this->client->request(
            'GET',
            $uri,
            server: ['HTTP_TURBO_FRAME' => TurboFrame::PAGE_ID],
        );
    }

    /**
     * @param array{string, ?string, string, string} $english
     * @param array{string, ?string, string, string} $polish
     */
    private function persistTask(
        int $position,
        ?string $thumbnail,
        array $english,
        array $polish,
    ): void {
        $task = new Task();
        $task->setPosition($position);
        $task->setThumbnail($thumbnail);
        $this->addTranslation($task, LocaleEnum::EN, ...$english);
        $this->addTranslation($task, LocaleEnum::PL, ...$polish);
        $this->entityManager->persist($task);
        $this->entityManager->flush();
    }

    private function addTranslation(
        Task $task,
        LocaleEnum $locale,
        string $name,
        ?string $description,
        string $tools,
        string $impact,
    ): void {
        $translation = new TaskTranslation();
        $translation->setLocale($locale);
        $translation->setName($name);
        $translation->setDescription($description);
        $translation->setTools($tools);
        $translation->setImpact($impact);
        $task->addTranslation($translation);
    }
}
