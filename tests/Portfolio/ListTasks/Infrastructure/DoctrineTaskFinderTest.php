<?php

declare(strict_types=1);

namespace App\Tests\Portfolio\ListTasks\Infrastructure;

use App\Portfolio\ListTasks\Application\TaskView;
use App\Portfolio\ListTasks\Infrastructure\DoctrineTaskFinder;
use App\Portfolio\Shared\Infrastructure\Persistence\Doctrine\Entity\Task;
use App\Portfolio\Shared\Infrastructure\Persistence\Doctrine\Entity\TaskTranslation;
use App\Shared\Domain\LocaleEnum;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bridge\Doctrine\Middleware\Debug\DebugDataHolder;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @internal
 *
 * @covers \App\Portfolio\ListTasks\Infrastructure\DoctrineTaskFinder
 */
final class DoctrineTaskFinderTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private DoctrineTaskFinder $finder;

    protected function setUp(): void
    {
        self::bootKernel(['debug' => true]);

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $this->entityManager = $entityManager;
        new SchemaTool($entityManager)->createSchema($entityManager->getMetadataFactory()->getAllMetadata());
        $this->finder = new DoctrineTaskFinder($entityManager);
    }

    public function test_it_returns_an_empty_list(): void
    {
        self::assertSame([], $this->finder->findAll('en'));
    }

    public function test_it_orders_all_tasks_and_projects_each_requested_locale(): void
    {
        $this->persistTask(2, 'second.webp', ['Second', 'Second description', 'PHP', 'Second impact'], ['Drugi', 'Drugi opis', 'PHP', 'Drugi wpływ']);
        $this->persistTask(1, null, ['First', null, 'SQL', 'First impact'], ['Pierwszy', null, 'SQL', 'Pierwszy wpływ']);

        self::assertEquals([
            new TaskView(null, 'First', null, 'SQL', 'First impact'),
            new TaskView('second.webp', 'Second', 'Second description', 'PHP', 'Second impact'),
        ], $this->finder->findAll('en'));
        self::assertEquals([
            new TaskView(null, 'Pierwszy', null, 'SQL', 'Pierwszy wpływ'),
            new TaskView('second.webp', 'Drugi', 'Drugi opis', 'PHP', 'Drugi wpływ'),
        ], $this->finder->findAll('pl'));
    }

    public function test_it_keeps_a_task_with_a_missing_requested_translation_and_null_texts(): void
    {
        $this->persistTask(1, 'task.webp', null, ['Tylko polski', 'Opis', 'SQL', 'Wpływ']);

        self::assertEquals([
            new TaskView('task.webp', null, null, null, null),
        ], $this->finder->findAll('en'));
    }

    public function test_it_executes_one_locale_filtered_and_position_ordered_query(): void
    {
        $this->persistTask(1, null, ['Task', null, 'PHP', 'Impact'], ['Zadanie', null, 'PHP', 'Wpływ']);
        $debugDataHolder = self::getContainer()->get('doctrine.debug_data_holder');
        self::assertInstanceOf(DebugDataHolder::class, $debugDataHolder);
        $debugDataHolder->reset();

        $this->finder->findAll('pl');

        /** @var array<string, list<array{sql: string}>> $queryData */
        $queryData = $debugDataHolder->getData();
        $queries = [];

        foreach ($queryData as $connectionQueries) {
            array_push($queries, ...$connectionQueries);
        }

        self::assertCount(1, $queries);
        self::assertStringContainsString('FROM app_task ', $queries[0]['sql']);
        self::assertStringContainsString('LEFT JOIN app_task_translation ', $queries[0]['sql']);
        self::assertStringContainsString('locale', $queries[0]['sql']);
        self::assertStringContainsString('ORDER BY', $queries[0]['sql']);
        self::assertStringContainsString('position', $queries[0]['sql']);
    }

    /**
     * @param null|array{string, ?string, string, string} $english
     * @param null|array{string, ?string, string, string} $polish
     */
    private function persistTask(
        int $position,
        ?string $thumbnail,
        ?array $english,
        ?array $polish,
    ): void {
        $task = new Task();
        $task->setPosition($position);
        $task->setThumbnail($thumbnail);

        if ($english !== null) {
            $this->addTranslation($task, LocaleEnum::EN, ...$english);
        }

        if ($polish !== null) {
            $this->addTranslation($task, LocaleEnum::PL, ...$polish);
        }

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
