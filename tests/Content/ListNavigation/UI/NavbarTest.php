<?php

declare(strict_types=1);

namespace App\Tests\Content\ListNavigation\UI;

use App\Content\ListNavigation\Application\ListNavigationHandler;
use App\Content\ListNavigation\Application\NavigationCandidate;
use App\Content\ListNavigation\Application\NavigationFinder;
use App\Content\ListNavigation\Application\NavigationItemView;
use App\Content\ListNavigation\UI\Navbar;
use App\Content\Shared\Infrastructure\Persistence\Doctrine\Entity\BlockCollection;
use App\Content\Shared\Infrastructure\Persistence\Doctrine\Entity\Page;
use App\Content\Shared\Infrastructure\Persistence\Doctrine\Entity\PageTranslation;
use App\Shared\Domain\LocaleEnum;
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
 * @covers \App\Content\ListNavigation\UI\Navbar
 */
final class NavbarTest extends WebTestCase
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

    public function test_rendered_navigation_preserves_publication_order_locales_fallback_and_active_link(): void
    {
        $this->persistPage(
            position: 2,
            published: true,
            english: ['second-page', 'Second page', null],
            polish: ['druga-strona', 'Druga strona', 'Drugie menu'],
        );
        $this->persistPage(
            position: 1,
            published: true,
            english: ['first-page', 'First page', 'First menu'],
            polish: ['pierwsza-strona', 'Pierwsza strona', null],
        );
        $this->persistPage(
            position: 0,
            published: false,
            english: ['hidden-page', 'Hidden page', 'Hidden menu'],
            polish: ['ukryta-strona', 'Ukryta strona', 'Ukryte menu'],
        );

        $this->client->request('GET', '/en/first-page');

        self::assertResponseIsSuccessful();
        self::assertSelectorCount(2, 'nav[aria-label="Main navigation"] a.nav-link');
        self::assertSelectorTextSame('nav li:nth-child(1) a', 'First menu');
        self::assertSelectorTextSame('nav li:nth-child(2) a', 'Second page');
        self::assertSelectorExists('nav li:nth-child(1) a.active[href="/en/first-page"]');
        self::assertSelectorExists('nav li:nth-child(2) a[href="/en/second-page"]');

        $this->client->request('GET', '/pl/first-page');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextSame('nav li:nth-child(1) a', 'Pierwsza strona');
        self::assertSelectorTextSame('nav li:nth-child(2) a', 'Drugie menu');
        self::assertSelectorExists('nav li:nth-child(1) a.active[href="/pl/pierwsza-strona"]');
        self::assertSelectorExists('nav li:nth-child(2) a[href="/pl/druga-strona"]');
    }

    public function test_component_is_a_thin_adapter_from_twig_and_request_to_the_handler(): void
    {
        $items = [new NavigationItemView('o-mnie', 'O mnie', true)];
        $finder = $this->createMock(NavigationFinder::class);
        $finder
            ->expects(self::once())
            ->method('findPublished')
            ->with('pl')
            ->willReturn([new NavigationCandidate('o-mnie', 'O mnie', 1, ['about', 'o-mnie'])])
        ;
        $requestStack = new RequestStack();
        $requestStack->push(new Request(attributes: ['slug' => 'about']));
        $navbar = new Navbar(new ListNavigationHandler($finder), $requestStack);
        $navbar->locale = LocaleEnum::PL;

        $navbar->postMount();

        self::assertEquals($items, $navbar->pages);
    }

    public function test_rendering_navigation_executes_one_navigation_query(): void
    {
        $this->persistPage(
            position: 1,
            published: true,
            english: ['first-page', 'First page', null],
            polish: ['pierwsza-strona', 'Pierwsza strona', null],
        );
        $this->persistPage(
            position: 2,
            published: true,
            english: ['second-page', 'Second page', null],
            polish: ['druga-strona', 'Druga strona', null],
        );
        $this->client->enableProfiler();

        $this->client->request('GET', '/en/first-page');

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

        $pageQueries = array_values(array_filter(
            $queries,
            static fn (array $query): bool => str_contains($query['sql'], 'FROM app_page '),
        ));
        $navigationQueries = array_values(array_filter(
            $pageQueries,
            static fn (array $query): bool => !str_contains($query['sql'], 'app_page_block'),
        ));

        self::assertCount(2, $pageQueries, 'Expected one page-content query and one navigation query.');
        self::assertCount(1, $navigationQueries);
    }

    /**
     * @param null|array{string, string, ?string} $english
     * @param null|array{string, string, ?string} $polish
     */
    private function persistPage(
        int $position,
        bool $published,
        ?array $english,
        ?array $polish,
    ): void {
        $page = new Page();
        $page->setPosition($position);
        $page->setPublished($published);
        $page->setContentTemplate('default.html.twig');

        if ($english !== null) {
            $this->addTranslation($page, LocaleEnum::EN, ...$english);
        }

        if ($polish !== null) {
            $this->addTranslation($page, LocaleEnum::PL, ...$polish);
        }

        $this->entityManager->persist($page);
        $this->entityManager->flush();
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
