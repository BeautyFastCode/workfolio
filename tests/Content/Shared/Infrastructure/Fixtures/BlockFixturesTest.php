<?php

declare(strict_types=1);

namespace App\Tests\Content\Shared\Infrastructure\Fixtures;

use App\Content\Shared\Infrastructure\Fixtures\BlockFixtures;
use App\Content\Shared\Infrastructure\Persistence\Doctrine\Entity\Block;
use App\Content\Shared\Infrastructure\Persistence\Doctrine\Entity\BlockCollection;
use App\Content\Shared\Infrastructure\Persistence\Doctrine\Entity\BlockTranslation;
use App\Content\Shared\Infrastructure\Persistence\Doctrine\Enum\BlockType;
use App\Shared\Domain\LocaleEnum;
use Doctrine\Bundle\FixturesBundle\Loader\FixturesProvider;
use Doctrine\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class BlockFixturesTest extends KernelTestCase
{
    public function test_fixture_remains_registered_with_the_same_group_and_data(): void
    {
        self::bootKernel();

        $fixturesProvider = self::getContainer()->get('doctrine.fixtures.loader');
        self::assertInstanceOf(FixturesProvider::class, $fixturesProvider);

        $fixtures = $fixturesProvider->getFixtures(['block']);
        self::assertCount(1, $fixtures);
        $fixture = $fixtures[0];
        self::assertInstanceOf(BlockFixtures::class, $fixture);
        self::assertSame(['block'], BlockFixtures::getGroups());

        $collections = [];
        $blocks = [];
        $translations = [];
        $manager = $this->createMock(ObjectManager::class);
        $manager
            ->expects($this->exactly(17))
            ->method('persist')
            ->willReturnCallback(static function (object $entity) use (&$collections, &$blocks, &$translations): void {
                if ($entity instanceof BlockCollection) {
                    $collections[] = $entity;
                }

                if ($entity instanceof Block) {
                    $blocks[] = $entity;
                }

                if ($entity instanceof BlockTranslation) {
                    $translations[] = $entity;
                }
            })
        ;
        $manager->expects($this->once())->method('flush');

        $fixture->load($manager);

        self::assertCount(2, $collections);
        self::assertCount(5, $blocks);
        self::assertCount(10, $translations);
        self::assertSame(['header', 'footer'], array_map(
            static fn (BlockCollection $collection): string => $collection->getName(),
            $collections,
        ));
        self::assertSame([4, 1], array_map(
            static fn (BlockCollection $collection): int => $collection->getBlocks()->count(),
            $collections,
        ));
        self::assertSame([
            ['site_name', 1, BlockType::TEXT],
            ['title', 2, BlockType::TEXT],
            ['subtitle', 3, BlockType::TEXT],
            ['about', 4, BlockType::TEXT],
            ['copyright', 1, BlockType::TEXT],
        ], array_map(
            static fn (Block $block): array => [$block->getName(), $block->getPosition(), $block->getType()],
            $blocks,
        ));
        self::assertSame('workfolio', $blocks[0]->getContent(LocaleEnum::EN));
        self::assertSame('workfolio', $blocks[0]->getContent(LocaleEnum::PL));
        self::assertSame('Copyright © 2026 Name and Surname', $blocks[4]->getContent(LocaleEnum::EN));
        self::assertSame('Prawa autorskie © 2026 Imię i nazwisko', $blocks[4]->getContent(LocaleEnum::PL));
    }
}
