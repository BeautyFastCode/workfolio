<?php

declare(strict_types=1);

namespace App\Tests\Portfolio\Shared\Infrastructure\Fixtures;

use App\Portfolio\Shared\Infrastructure\Fixtures\TaskFixtures;
use App\Portfolio\Shared\Infrastructure\Persistence\Doctrine\Entity\Task;
use App\Portfolio\Shared\Infrastructure\Persistence\Doctrine\Entity\TaskTranslation;
use App\Shared\Domain\LocaleEnum;
use Doctrine\Bundle\FixturesBundle\Loader\FixturesProvider;
use Doctrine\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class TaskFixturesTest extends KernelTestCase
{
    public function test_fixture_remains_registered_with_the_same_group_and_data(): void
    {
        self::bootKernel();

        $fixturesProvider = self::getContainer()->get('doctrine.fixtures.loader');
        self::assertInstanceOf(FixturesProvider::class, $fixturesProvider);

        $fixtures = $fixturesProvider->getFixtures(['task']);
        self::assertCount(1, $fixtures);
        $fixture = $fixtures[0];
        self::assertInstanceOf(TaskFixtures::class, $fixture);
        self::assertSame(['task'], TaskFixtures::getGroups());

        $tasks = [];
        $translations = [];
        $manager = $this->createMock(ObjectManager::class);
        $manager
            ->expects($this->exactly(9))
            ->method('persist')
            ->willReturnCallback(static function (object $entity) use (&$tasks, &$translations): void {
                if ($entity instanceof Task) {
                    $tasks[] = $entity;
                }

                if ($entity instanceof TaskTranslation) {
                    $translations[] = $entity;
                }
            })
        ;
        $manager->expects($this->once())->method('flush');

        $fixture->load($manager);

        self::assertCount(3, $tasks);
        self::assertCount(6, $translations);
        self::assertSame([1, 2, 3], array_map(
            static fn (Task $task): int => $task->getPosition(),
            $tasks,
        ));
        self::assertSame([
            ['HR Analytics Report for Workforce Planning', 'Raport analityczny HR do planowania zatrudnienia'],
            ['Employee Engagement Survey Analysis', 'Analiza ankiety zaangażowania pracowników'],
            ['Recruitment Pipeline Optimization', 'Optymalizacja procesu rekrutacyjnego'],
        ], array_map(
            static fn (Task $task): array => [$task->getNameEn(), $task->getNamePl()],
            $tasks,
        ));
        self::assertSame([
            'Built an interactive workforce planning dashboard to help HR teams monitor headcount, overtime, turnover risk and department-level staffing needs.',
            'Excel, Power BI, SQL',
            'Reduced overtime cost by 19% and improved staffing allocation efficiency.',
        ], [
            $tasks[0]->getDescription(LocaleEnum::EN),
            $tasks[0]->getTools(LocaleEnum::EN),
            $tasks[0]->getImpact(LocaleEnum::EN),
        ]);
        self::assertSame([
            'Stworzenie procesu raportowania kandydatów na kolejnych etapach rekrutacji, pomiar czasu zatrudnienia oraz identyfikacja wąskich gardeł w lejku rekrutacyjnym.',
            'Airtable, SQL, Looker Studio',
            'Skrócenie średniego czasu zatrudnienia o 22% oraz poprawa przejrzystości procesu dla hiring managerów.',
        ], [
            $tasks[2]->getDescription(LocaleEnum::PL),
            $tasks[2]->getTools(LocaleEnum::PL),
            $tasks[2]->getImpact(LocaleEnum::PL),
        ]);
    }
}
