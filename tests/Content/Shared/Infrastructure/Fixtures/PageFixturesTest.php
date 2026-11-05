<?php

declare(strict_types=1);

namespace App\Tests\Content\Shared\Infrastructure\Fixtures;

use App\Content\Shared\Infrastructure\Fixtures\PageFixtures;
use App\Content\Shared\Infrastructure\Persistence\Doctrine\Entity\Page;
use App\Content\Shared\Infrastructure\Persistence\Doctrine\Entity\PageBlock;
use App\Content\Shared\Infrastructure\Persistence\Doctrine\Entity\PageBlockTranslation;
use App\Content\Shared\Infrastructure\Persistence\Doctrine\Entity\PageTranslation;
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
final class PageFixturesTest extends KernelTestCase
{
    public function test_fixture_remains_registered_with_the_same_group_and_data(): void
    {
        self::bootKernel();

        $fixturesProvider = self::getContainer()->get('doctrine.fixtures.loader');
        self::assertInstanceOf(FixturesProvider::class, $fixturesProvider);

        $fixtures = $fixturesProvider->getFixtures(['page']);
        self::assertCount(1, $fixtures);
        $fixture = $fixtures[0];
        self::assertInstanceOf(PageFixtures::class, $fixture);
        self::assertSame(['page'], PageFixtures::getGroups());

        $pages = [];
        $pageTranslations = [];
        $blocks = [];
        $blockTranslations = [];
        $manager = $this->createMock(ObjectManager::class);
        $manager
            ->expects($this->exactly(51))
            ->method('persist')
            ->willReturnCallback(static function (object $entity) use (&$pages, &$pageTranslations, &$blocks, &$blockTranslations): void {
                match (true) {
                    $entity instanceof Page => $pages[] = $entity,
                    $entity instanceof PageTranslation => $pageTranslations[] = $entity,
                    $entity instanceof PageBlock => $blocks[] = $entity,
                    $entity instanceof PageBlockTranslation => $blockTranslations[] = $entity,
                    default => null,
                };
            })
        ;
        $manager->expects($this->once())->method('flush');

        $fixture->load($manager);

        self::assertCount(5, $pages);
        self::assertCount(10, $pageTranslations);
        self::assertCount(12, $blocks);
        self::assertCount(24, $blockTranslations);
        self::assertSame([
            ['Technologies', 'Technologie', 'technologies', 'technologie', 1, 'technologies.html.twig', 3],
            ['Tasks', 'Zadania', 'tasks', 'zadania', 2, 'default.html.twig', 2],
            ['About me', 'O mnie', 'about', 'o-mnie', 3, 'default.html.twig', 2],
            ['Feel free to contact me.', 'Możesz się ze mną skontaktować.', 'contact', 'kontakt', 4, 'default.html.twig', 3],
            ['Portfolio of projects', 'Portfolio projektów', 'projects', 'projekty', 5, 'default.html.twig', 2],
        ], array_map(
            static fn (Page $page): array => [
                $page->getTitleEn(),
                $page->getTitlePl(),
                $page->getSlug(LocaleEnum::EN),
                $page->getSlug(LocaleEnum::PL),
                $page->getPosition(),
                $page->getContentTemplate(),
                $page->getBlocks()->count(),
            ],
            $pages,
        ));
        self::assertSame([true, true, true, true, true], array_map(
            static fn (Page $page): bool => $page->isPublished(),
            $pages,
        ));
        self::assertSame([
            ['block_1', 1, BlockType::HTML],
            ['title_1', 2, BlockType::TEXT],
            ['title_2', 3, BlockType::TEXT],
            ['block_1', 1, BlockType::HTML],
            ['block_2', 2, BlockType::TWIG],
            ['block_1', 1, BlockType::HTML],
            ['block_2', 2, BlockType::HTML],
            ['block_1', 1, BlockType::HTML],
            ['block_2', 2, BlockType::TWIG],
            ['block_3', 3, BlockType::TWIG],
            ['block_1', 1, BlockType::HTML],
            ['block_2', 2, BlockType::HTML],
        ], array_map(
            static fn (PageBlock $block): array => [$block->getName(), $block->getPosition(), $block->getType()],
            $blocks,
        ));
        self::assertSame('Key technologies', $blocks[1]->getContent(LocaleEnum::EN));
        self::assertSame('Kluczowe technologie', $blocks[1]->getContent(LocaleEnum::PL));
        self::assertSame('[[Tasks]]', $blocks[4]->getContent(LocaleEnum::EN));
        self::assertSame('[[ContactForm]]', $blocks[9]->getContent(LocaleEnum::PL));
    }
}
