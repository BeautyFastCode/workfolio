<?php

declare(strict_types=1);

namespace App\Tests\Content\ListNavigation\Infrastructure;

use App\Content\ListNavigation\Application\NavigationCandidate;
use App\Content\ListNavigation\Infrastructure\DoctrineNavigationFinder;
use App\Content\Shared\Infrastructure\Persistence\Doctrine\Entity\Page;
use App\Content\Shared\Infrastructure\Persistence\Doctrine\Entity\PageTranslation;
use App\Shared\Domain\LocaleEnum;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bridge\Doctrine\Middleware\Debug\DebugDataHolder;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @internal
 *
 * @covers \App\Content\ListNavigation\Infrastructure\DoctrineNavigationFinder
 */
final class DoctrineNavigationFinderTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private DoctrineNavigationFinder $finder;

    protected function setUp(): void
    {
        self::bootKernel(['debug' => true]);

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $this->entityManager = $entityManager;
        new SchemaTool($entityManager)->createSchema($entityManager->getMetadataFactory()->getAllMetadata());
        $this->finder = new DoctrineNavigationFinder($entityManager);
    }

    public function test_it_returns_an_empty_navigation(): void
    {
        self::assertSame([], $this->finder->findPublished('en'));
    }

    public function test_it_filters_unpublished_pages_and_orders_published_pages_by_position(): void
    {
        $this->persistPage(3, true, ['third', 'Third', null]);
        $this->persistPage(1, true, ['first', 'First', 'First menu']);
        $this->persistPage(0, false, ['hidden', 'Hidden', 'Hidden menu']);

        self::assertEquals(
            [
                new NavigationCandidate('first', 'First menu', 1, ['first']),
                new NavigationCandidate('third', 'Third', 3, ['third']),
            ],
            $this->finder->findPublished('en'),
        );
    }

    public function test_it_projects_each_locale_and_uses_the_page_title_when_menu_title_is_empty(): void
    {
        $page = $this->newPage(1, true);
        $this->addTranslation($page, LocaleEnum::EN, 'about', 'About', null);
        $this->addTranslation($page, LocaleEnum::PL, 'o-mnie', 'O mnie', 'Poznaj mnie');
        $this->entityManager->persist($page);
        $this->entityManager->flush();

        self::assertEquals(
            [new NavigationCandidate('about', 'About', 1, ['about', 'o-mnie'])],
            $this->finder->findPublished('en'),
        );
        self::assertEquals(
            [new NavigationCandidate('o-mnie', 'Poznaj mnie', 1, ['about', 'o-mnie'])],
            $this->finder->findPublished('pl'),
        );

        $english = $page->getTranslation(LocaleEnum::EN);
        self::assertInstanceOf(PageTranslation::class, $english);
        $english->setMenuTitle('');
        $this->entityManager->flush();

        self::assertSame('About', $this->finder->findPublished('en')[0]->menuTitle);
    }

    public function test_it_keeps_a_page_with_a_missing_requested_translation_and_exposes_null_values(): void
    {
        $this->persistPage(1, true, null, ['tylko-polski', 'Tylko polski', null]);

        self::assertEquals(
            [new NavigationCandidate(null, null, 1, ['tylko-polski'])],
            $this->finder->findPublished('en'),
        );
    }

    public function test_it_executes_one_page_and_translation_query_without_loading_blocks(): void
    {
        $this->persistPage(1, true, ['first', 'First', null]);
        $this->persistPage(2, true, ['second', 'Second', null]);

        $debugDataHolder = self::getContainer()->get('doctrine.debug_data_holder');
        self::assertInstanceOf(DebugDataHolder::class, $debugDataHolder);
        $debugDataHolder->reset();

        $this->finder->findPublished('en');

        /** @var array<string, list<array{sql: string}>> $queryData */
        $queryData = $debugDataHolder->getData();
        $queries = [];

        foreach ($queryData as $connectionQueries) {
            array_push($queries, ...$connectionQueries);
        }

        self::assertCount(1, $queries);
        self::assertStringContainsString('FROM app_page ', $queries[0]['sql']);
        self::assertStringContainsString('LEFT JOIN app_page_translation ', $queries[0]['sql']);
        self::assertStringContainsString('ORDER BY', $queries[0]['sql']);
        self::assertStringContainsString('position', $queries[0]['sql']);
        self::assertStringNotContainsString('app_page_block', $queries[0]['sql']);
    }

    /**
     * @param null|array{string, string, ?string} $english
     * @param null|array{string, string, ?string} $polish
     */
    private function persistPage(
        int $position,
        bool $published,
        ?array $english,
        ?array $polish = null,
    ): void {
        $page = $this->newPage($position, $published);

        if ($english !== null) {
            $this->addTranslation($page, LocaleEnum::EN, ...$english);
        }

        if ($polish !== null) {
            $this->addTranslation($page, LocaleEnum::PL, ...$polish);
        }

        $this->entityManager->persist($page);
        $this->entityManager->flush();
    }

    private function newPage(int $position, bool $published): Page
    {
        $page = new Page();
        $page->setPosition($position);
        $page->setPublished($published);
        $page->setContentTemplate('default.html.twig');

        return $page;
    }

    private function addTranslation(
        Page $page,
        LocaleEnum $locale,
        string $slug,
        string $title,
        ?string $menuTitle,
    ): void {
        $translation = new PageTranslation();
        $translation->setLocale($locale);
        $translation->setSlug($slug);
        $translation->setTitle($title);
        $translation->setMenuTitle($menuTitle);
        $page->addTranslation($translation);
    }
}
