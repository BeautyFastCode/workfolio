<?php

declare(strict_types=1);

namespace App\Tests\Portfolio\Shared\Infrastructure\Fixtures;

use App\Portfolio\Shared\Infrastructure\Fixtures\StatisticFixtures;
use App\Portfolio\Shared\Infrastructure\Persistence\Doctrine\Entity\StatisticGroup;
use App\Portfolio\Shared\Infrastructure\Persistence\Doctrine\Entity\StatisticGroupTranslation;
use App\Portfolio\Shared\Infrastructure\Persistence\Doctrine\Entity\StatisticItem;
use App\Portfolio\Shared\Infrastructure\Persistence\Doctrine\Entity\StatisticItemTranslation;
use Doctrine\Bundle\FixturesBundle\Loader\FixturesProvider;
use Doctrine\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class StatisticFixturesTest extends KernelTestCase
{
    public function test_fixture_remains_registered_with_the_same_group_and_data(): void
    {
        self::bootKernel();

        $fixturesProvider = self::getContainer()->get('doctrine.fixtures.loader');
        self::assertInstanceOf(FixturesProvider::class, $fixturesProvider);

        $fixtures = $fixturesProvider->getFixtures(['statistic']);
        self::assertCount(1, $fixtures);
        $fixture = $fixtures[0];
        self::assertInstanceOf(StatisticFixtures::class, $fixture);
        self::assertSame(['statistic'], StatisticFixtures::getGroups());

        $groups = [];
        $groupTranslations = [];
        $items = [];
        $itemTranslations = [];
        $manager = $this->createMock(ObjectManager::class);
        $manager
            ->expects($this->exactly(45))
            ->method('persist')
            ->willReturnCallback(static function (object $entity) use (&$groups, &$groupTranslations, &$items, &$itemTranslations): void {
                match (true) {
                    $entity instanceof StatisticGroup => $groups[] = $entity,
                    $entity instanceof StatisticGroupTranslation => $groupTranslations[] = $entity,
                    $entity instanceof StatisticItem => $items[] = $entity,
                    $entity instanceof StatisticItemTranslation => $itemTranslations[] = $entity,
                    default => null,
                };
            })
        ;
        $manager->expects($this->once())->method('flush');

        $fixture->load($manager);

        self::assertCount(3, $groups);
        self::assertCount(6, $groupTranslations);
        self::assertCount(12, $items);
        self::assertCount(24, $itemTranslations);
        self::assertSame([
            ['Company achievements', 'Osiągnięcia firmy', 1, 4],
            ['Team statistics', 'Statystyki zespołu', 2, 4],
            ['Business growth', 'Wzrost biznesu', 3, 4],
        ], array_map(
            static fn (StatisticGroup $group): array => [
                $group->getNameEn(),
                $group->getNamePl(),
                $group->getPosition(),
                $group->getItems()->count(),
            ],
            $groups,
        ));
        self::assertSame([
            ['21+', 1, 'Client satisfaction rate', 'Wskaźnik zadowolenia klienta'],
            ['300', 2, 'Created projects', 'Utworzone projekty'],
            ['94%', 3, 'Better performance', 'Lepsza wydajność'],
            ['125', 4, 'Drunk coffee', 'Wypite kawy'],
            ['18', 1, 'Team members', 'Członków zespołu'],
            ['1M+', 2, 'Lines of code', 'Linii kodu'],
            ['8+', 3, 'Years of experience', 'Lat doświadczenia'],
            ['12', 4, 'Countries served', 'Krajów'],
            ['150%', 1, 'Revenue growth', 'Wzrost przychodów'],
            ['85+', 2, 'Business partners', 'Partnerów biznesowych'],
            ['14', 3, 'Industry awards', 'Nagrody branżowe'],
            ['99.9%', 4, 'System uptime', 'Czas sprawności systemu'],
        ], array_map(
            static fn (StatisticItem $item): array => [
                $item->getValue(),
                $item->getPosition(),
                $item->getNameEn(),
                $item->getNamePl(),
            ],
            $items,
        ));
    }
}
