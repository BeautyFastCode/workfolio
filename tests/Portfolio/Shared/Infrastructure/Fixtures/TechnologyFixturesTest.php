<?php

declare(strict_types=1);

namespace App\Tests\Portfolio\Shared\Infrastructure\Fixtures;

use App\Portfolio\Shared\Infrastructure\Fixtures\TechnologyFixtures;
use App\Portfolio\Shared\Infrastructure\Persistence\Doctrine\Entity\TechnologyGroup;
use App\Portfolio\Shared\Infrastructure\Persistence\Doctrine\Entity\TechnologyGroupTranslation;
use App\Portfolio\Shared\Infrastructure\Persistence\Doctrine\Entity\TechnologyItem;
use Doctrine\Bundle\FixturesBundle\Loader\FixturesProvider;
use Doctrine\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class TechnologyFixturesTest extends KernelTestCase
{
    public function test_fixture_remains_registered_with_the_same_group_and_data(): void
    {
        self::bootKernel();

        $fixturesProvider = self::getContainer()->get('doctrine.fixtures.loader');
        self::assertInstanceOf(FixturesProvider::class, $fixturesProvider);

        $fixtures = $fixturesProvider->getFixtures(['technology']);
        self::assertCount(1, $fixtures);
        $fixture = $fixtures[0];
        self::assertInstanceOf(TechnologyFixtures::class, $fixture);
        self::assertSame(['technology'], TechnologyFixtures::getGroups());

        $groups = [];
        $translations = [];
        $items = [];
        $manager = $this->createMock(ObjectManager::class);
        $manager
            ->expects($this->exactly(36))
            ->method('persist')
            ->willReturnCallback(static function (object $entity) use (&$groups, &$translations, &$items): void {
                match (true) {
                    $entity instanceof TechnologyGroup => $groups[] = $entity,
                    $entity instanceof TechnologyGroupTranslation => $translations[] = $entity,
                    $entity instanceof TechnologyItem => $items[] = $entity,
                    default => null,
                };
            })
        ;
        $manager->expects($this->once())->method('flush');

        $fixture->load($manager);

        self::assertCount(5, $groups);
        self::assertCount(10, $translations);
        self::assertCount(21, $items);
        self::assertSame([
            ['PHP Symfony', 'PHP Symfony', 1, true, 4],
            ['Backend / Databases', 'Backend / Baza danych', 2, true, 3],
            ['Tools', 'Narzędzia', 3, true, 4],
            ['Backend / Databases', 'Backend / Baza danych', 4, false, 3],
            ['Tools', 'Narzędzia', 5, false, 7],
        ], array_map(
            static fn (TechnologyGroup $group): array => [
                $group->getNameEn(),
                $group->getNamePl(),
                $group->getPosition(),
                $group->isHighlighted(),
                $group->getItems()->count(),
            ],
            $groups,
        ));
        self::assertSame([
            'React', 'Node.js', 'TypeScript', 'Express.js',
            'Node.js', 'MongoDB', 'PostgreSQL',
            'Git', 'GitHub', 'Docker', 'VS Code',
            'Node.js', 'MongoDB', 'PostgreSQL',
            'Git', 'GitHub', 'Docker', 'VS Code', 'Node.js', 'MongoDB', 'PostgreSQL',
        ], array_map(
            static fn (TechnologyItem $item): string => $item->getName(),
            $items,
        ));
        self::assertSame('fa-regular fa-face-smile', $items[0]->getIcon());
        self::assertSame(1, $items[0]->getPosition());
        self::assertSame('fa-solid fa-clock', $items[20]->getIcon());
        self::assertSame(7, $items[20]->getPosition());
    }
}
