<?php

declare(strict_types=1);

namespace App\Tests\Content\GetLayoutContent\Infrastructure;

use App\Content\GetLayoutContent\Application\LayoutContentBlockView;
use App\Content\GetLayoutContent\Application\LayoutContentView;
use App\Content\GetLayoutContent\Infrastructure\DoctrineLayoutContentFinder;
use App\Content\Shared\Infrastructure\Persistence\Doctrine\Entity\Block;
use App\Content\Shared\Infrastructure\Persistence\Doctrine\Entity\BlockCollection;
use App\Content\Shared\Infrastructure\Persistence\Doctrine\Entity\BlockTranslation;
use App\Content\Shared\Infrastructure\Persistence\Doctrine\Enum\BlockType;
use App\Shared\Domain\LocaleEnum;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bridge\Doctrine\Middleware\Debug\DebugDataHolder;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @internal
 *
 * @covers \App\Content\GetLayoutContent\Infrastructure\DoctrineLayoutContentFinder
 */
final class DoctrineLayoutContentFinderTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private DoctrineLayoutContentFinder $finder;

    protected function setUp(): void
    {
        self::bootKernel(['debug' => true]);

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $this->entityManager = $entityManager;
        new SchemaTool($entityManager)->createSchema($entityManager->getMetadataFactory()->getAllMetadata());
        $this->finder = new DoctrineLayoutContentFinder($entityManager);
    }

    public function test_it_distinguishes_an_empty_collection_from_a_missing_collection(): void
    {
        $collection = new BlockCollection();
        $collection->setName('header');
        $this->entityManager->persist($collection);
        $this->entityManager->flush();

        self::assertEquals(new LayoutContentView('header', []), $this->finder->find('header', 'en'));
        self::assertNull($this->finder->find('footer', 'en'));
    }

    public function test_it_orders_blocks_and_projects_each_requested_locale(): void
    {
        $collection = $this->newCollection('header');
        $this->addBlock($collection, 'second', 2, ['Second', 'Drugi'], ['second.webp', 'drugi.webp']);
        $this->addBlock($collection, 'first', 1, ['First', 'Pierwszy']);
        $this->entityManager->persist($collection);
        $this->entityManager->flush();

        self::assertEquals(new LayoutContentView('header', [
            new LayoutContentBlockView('first', 'First', null),
            new LayoutContentBlockView('second', 'Second', 'second.webp'),
        ]), $this->finder->find('header', 'en'));
        self::assertEquals(new LayoutContentView('header', [
            new LayoutContentBlockView('first', 'Pierwszy', null),
            new LayoutContentBlockView('second', 'Drugi', 'drugi.webp'),
        ]), $this->finder->find('header', 'pl'));
    }

    public function test_it_keeps_a_block_with_a_missing_requested_translation_and_null_values(): void
    {
        $collection = $this->newCollection('footer');
        $block = new Block();
        $block->setName('copyright');
        $block->setType(BlockType::TEXT);
        $block->setPosition(1);
        $translation = new BlockTranslation();
        $translation->setLocale(LocaleEnum::PL);
        $translation->setContent('Polska treść');
        $block->addTranslation($translation);
        $collection->addBlock($block);
        $this->entityManager->persist($collection);
        $this->entityManager->flush();

        self::assertEquals(new LayoutContentView('footer', [
            new LayoutContentBlockView('copyright', null, null),
        ]), $this->finder->find('footer', 'en'));
    }

    public function test_it_executes_one_filtered_ordered_collection_query_with_localised_translations(): void
    {
        $collection = $this->newCollection('header');
        $this->addBlock($collection, 'title', 1, ['Title', 'Tytuł']);
        $this->entityManager->persist($collection);
        $this->entityManager->flush();

        $debugDataHolder = self::getContainer()->get('doctrine.debug_data_holder');
        self::assertInstanceOf(DebugDataHolder::class, $debugDataHolder);
        $debugDataHolder->reset();

        $this->finder->find('header', 'pl');

        /** @var array<string, list<array{sql: string}>> $queryData */
        $queryData = $debugDataHolder->getData();
        $queries = [];

        foreach ($queryData as $connectionQueries) {
            array_push($queries, ...$connectionQueries);
        }

        self::assertCount(1, $queries);
        self::assertStringContainsString('FROM app_block_collection ', $queries[0]['sql']);
        self::assertStringContainsString('LEFT JOIN app_block ', $queries[0]['sql']);
        self::assertStringContainsString('LEFT JOIN app_block_translation ', $queries[0]['sql']);
        self::assertStringContainsString('locale', $queries[0]['sql']);
        self::assertStringContainsString('name', $queries[0]['sql']);
        self::assertStringContainsString('ORDER BY', $queries[0]['sql']);
        self::assertStringContainsString('position', $queries[0]['sql']);
    }

    private function newCollection(string $name): BlockCollection
    {
        $collection = new BlockCollection();
        $collection->setName($name);

        return $collection;
    }

    /**
     * @param array{string, string}   $content
     * @param array{?string, ?string} $images
     */
    private function addBlock(
        BlockCollection $collection,
        string $name,
        int $position,
        array $content,
        array $images = [null, null],
    ): void {
        $block = new Block();
        $block->setName($name);
        $block->setType($images === [null, null] ? BlockType::TEXT : BlockType::IMAGE);
        $block->setPosition($position);

        foreach ([LocaleEnum::EN, LocaleEnum::PL] as $index => $locale) {
            $translation = new BlockTranslation();
            $translation->setLocale($locale);
            $translation->setContent($content[$index]);
            $translation->setImage($images[$index]);
            $block->addTranslation($translation);
        }

        $collection->addBlock($block);
    }
}
