<?php

declare(strict_types=1);

namespace App\Tests\Content\ShowPublishedPage\Infrastructure;

use App\Content\Shared\Infrastructure\Persistence\Doctrine\Entity\Page;
use App\Content\Shared\Infrastructure\Persistence\Doctrine\Entity\PageBlock;
use App\Content\Shared\Infrastructure\Persistence\Doctrine\Entity\PageBlockTranslation;
use App\Content\Shared\Infrastructure\Persistence\Doctrine\Entity\PageTranslation;
use App\Content\Shared\Infrastructure\Persistence\Doctrine\Enum\BlockType;
use App\Content\ShowPublishedPage\Application\DefaultPageSelection;
use App\Content\ShowPublishedPage\Application\PublishedPageBlockView;
use App\Content\ShowPublishedPage\Application\PublishedPageView;
use App\Content\ShowPublishedPage\Infrastructure\DoctrinePublishedPageFinder;
use App\Shared\Domain\LocaleEnum;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bridge\Doctrine\Middleware\Debug\DebugDataHolder;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @internal
 *
 * @covers \App\Content\ShowPublishedPage\Infrastructure\DoctrinePublishedPageFinder
 */
final class DoctrinePublishedPageFinderTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private DoctrinePublishedPageFinder $finder;

    protected function setUp(): void
    {
        self::bootKernel(['debug' => true]);

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $this->entityManager = $entityManager;
        new SchemaTool($entityManager)->createSchema($entityManager->getMetadataFactory()->getAllMetadata());
        $this->finder = new DoctrinePublishedPageFinder($entityManager);
    }

    public function test_it_finds_a_published_page_by_a_slug_from_any_translation_and_projects_requested_locale(): void
    {
        $this->persistPage(position: 1, published: true);

        self::assertEquals(
            new PublishedPageView(
                'strona-test-page',
                'Strona testowa',
                'Polskie menu',
                'default.html.twig',
                [new PublishedPageBlockView('intro', 'html', '<p>Polska treść</p>')],
            ),
            $this->finder->findPublishedBySlug('test-page', 'pl'),
        );
    }

    public function test_it_returns_null_for_a_missing_or_unpublished_page(): void
    {
        $this->persistPage(position: 1, published: false);

        self::assertNull($this->finder->findPublishedBySlug('missing', 'en'));
        self::assertNull($this->finder->findPublishedBySlug('test-page', 'en'));
    }

    public function test_it_selects_the_published_page_at_position_three_as_the_current_default(): void
    {
        $this->persistPage(position: 1, published: true, englishSlug: 'not-default');
        $this->persistPage(position: 3, published: false, englishSlug: 'unpublished-default');
        $this->persistPage(position: 3, published: true, englishSlug: 'current-default');

        $result = $this->finder->findDefaultPublished(DefaultPageSelection::current(), 'en');

        self::assertInstanceOf(PublishedPageView::class, $result);
        self::assertSame('current-default', $result->slug);
        self::assertSame('Test page', $result->title);
    }

    public function test_it_executes_one_filtered_query_with_page_translations_and_ordered_blocks(): void
    {
        $this->persistPage(position: 1, published: true);

        $debugDataHolder = self::getContainer()->get('doctrine.debug_data_holder');
        self::assertInstanceOf(DebugDataHolder::class, $debugDataHolder);
        $debugDataHolder->reset();

        $this->finder->findPublishedBySlug('test-page', 'en');

        /** @var array<string, list<array{sql: string}>> $queryData */
        $queryData = $debugDataHolder->getData();
        $queries = [];

        foreach ($queryData as $connectionQueries) {
            array_push($queries, ...$connectionQueries);
        }

        self::assertCount(1, $queries);
        self::assertStringContainsString('FROM app_page ', $queries[0]['sql']);
        self::assertStringContainsString('JOIN app_page_translation ', $queries[0]['sql']);
        self::assertStringContainsString('LEFT JOIN app_page_block ', $queries[0]['sql']);
        self::assertStringContainsString('LEFT JOIN app_page_block_translation ', $queries[0]['sql']);
        self::assertStringContainsString('published', $queries[0]['sql']);
        self::assertStringContainsString('slug', $queries[0]['sql']);
        self::assertStringContainsString('ORDER BY', $queries[0]['sql']);
        self::assertStringContainsString('position', $queries[0]['sql']);
    }

    private function persistPage(
        int $position,
        bool $published,
        string $englishSlug = 'test-page',
    ): void {
        $page = new Page();
        $page->setPosition($position);
        $page->setPublished($published);
        $page->setContentTemplate('default.html.twig');

        $english = new PageTranslation();
        $english->setLocale(LocaleEnum::EN);
        $english->setSlug($englishSlug);
        $english->setTitle('Test page');
        $english->setMenuTitle(null);
        $page->addTranslation($english);

        $polish = new PageTranslation();
        $polish->setLocale(LocaleEnum::PL);
        $polish->setSlug('strona-'.$englishSlug);
        $polish->setTitle('Strona testowa');
        $polish->setMenuTitle('Polskie menu');
        $page->addTranslation($polish);

        $block = new PageBlock();
        $block->setName('intro');
        $block->setType(BlockType::HTML);
        $block->setPosition(1);

        $englishBlock = new PageBlockTranslation();
        $englishBlock->setLocale(LocaleEnum::EN);
        $englishBlock->setContent('<p>English content</p>');
        $block->addTranslation($englishBlock);

        $polishBlock = new PageBlockTranslation();
        $polishBlock->setLocale(LocaleEnum::PL);
        $polishBlock->setContent('<p>Polska treść</p>');
        $block->addTranslation($polishBlock);
        $page->addBlock($block);

        $this->entityManager->persist($page);
        $this->entityManager->flush();
        $this->entityManager->clear();
    }
}
