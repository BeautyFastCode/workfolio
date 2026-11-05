<?php

declare(strict_types=1);

namespace App\Tests\Portfolio\ListTechnologies\UI;

use App\Content\Shared\Infrastructure\Persistence\Doctrine\Entity\Page;
use App\Content\Shared\Infrastructure\Persistence\Doctrine\Entity\PageTranslation;
use App\Portfolio\ListTechnologies\Application\ListTechnologiesHandler;
use App\Portfolio\ListTechnologies\Application\TechnologyFinder;
use App\Portfolio\ListTechnologies\Application\TechnologyGroupView;
use App\Portfolio\ListTechnologies\Application\TechnologyItemView;
use App\Portfolio\ListTechnologies\UI\Technologies;
use App\Portfolio\Shared\Infrastructure\Persistence\Doctrine\Entity\TechnologyGroup;
use App\Portfolio\Shared\Infrastructure\Persistence\Doctrine\Entity\TechnologyGroupTranslation;
use App\Portfolio\Shared\Infrastructure\Persistence\Doctrine\Entity\TechnologyItem;
use App\Shared\Domain\LocaleEnum;
use App\Shared\UI\TurboFrame;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bridge\Doctrine\DataCollector\DoctrineDataCollector;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpKernel\Profiler\Profile;

/**
 * @internal
 *
 * @covers \App\Portfolio\ListTechnologies\UI\Technologies
 */
final class TechnologiesTest extends WebTestCase
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
        $this->persistTechnologiesPage();
    }

    public function test_component_is_a_thin_adapter_preserving_its_locale_and_highlighted_inputs(): void
    {
        $groups = [new TechnologyGroupView('Pozostałe', [
            new TechnologyItemView('Docker', null),
        ])];
        $finder = $this->createMock(TechnologyFinder::class);
        $finder
            ->expects(self::once())
            ->method('find')
            ->with('pl', false)
            ->willReturn($groups)
        ;
        $component = new Technologies(new ListTechnologiesHandler($finder));
        $component->locale = LocaleEnum::PL;
        $component->highlighted = false;

        $component->postMount();

        self::assertSame($groups, $component->data);
    }

    public function test_html_preserves_filters_order_both_locales_missing_translation_and_null_icon(): void
    {
        $this->persistGroup(3, false, ['Other', 'Pozostałe'], [['Docker', 'docker-icon', 1]]);
        $this->persistGroup(2, true, ['Backend', 'Zaplecze'], [
            ['Symfony', 'symfony-icon', 2],
            ['PHP', null, 1],
        ]);
        $this->persistGroup(1, true, [null, 'Tylko polski'], []);

        $english = $this->requestPage('/en/technologies-page');

        self::assertResponseIsSuccessful();
        self::assertCount(2, $english->filter('.technologies'));
        self::assertSame(['', 'Backend'], $this->texts($english->filter('.technologies')->eq(0)->filter('h5')));
        self::assertSame(['PHP', 'Symfony'], $this->texts($english->filter('.technologies')->eq(0)->filter('span')));
        self::assertSame(['Other'], $this->texts($english->filter('.technologies')->eq(1)->filter('h5')));
        self::assertSame(['Docker'], $this->texts($english->filter('.technologies')->eq(1)->filter('span')));
        self::assertSelectorExists('.technologies i.symfony-icon');
        self::assertSelectorExists('.technologies i.docker-icon');
        self::assertSelectorCount(3, '.technologies i');

        $polish = $this->requestPage('/pl/technologies-page');

        self::assertResponseIsSuccessful();
        self::assertSame(['Tylko polski', 'Zaplecze'], $this->texts($polish->filter('.technologies')->eq(0)->filter('h5')));
        self::assertSame(['Pozostałe'], $this->texts($polish->filter('.technologies')->eq(1)->filter('h5')));
    }

    public function test_empty_results_render_both_existing_empty_containers(): void
    {
        $crawler = $this->requestPage('/en/technologies-page');

        self::assertResponseIsSuccessful();
        self::assertCount(2, $crawler->filter('.technologies'));
        self::assertCount(0, $crawler->filter('.technologies h5'));
        self::assertCount(0, $crawler->filter('.technologies .technologies-card'));
    }

    public function test_full_rendering_executes_one_shaped_query_per_component(): void
    {
        $this->persistGroup(1, true, ['Backend', 'Zaplecze'], [['PHP', null, 1]]);
        $this->persistGroup(2, false, ['Other', 'Pozostałe'], [['Docker', null, 1]]);
        $this->client->enableProfiler();

        $this->requestPage('/en/technologies-page');

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

        $technologyQueries = array_values(array_filter(
            $queries,
            static fn (array $query): bool => str_contains($query['sql'], 'FROM app_technology_group '),
        ));

        self::assertCount(2, $technologyQueries, 'Expected one query for each Technologies component.');

        foreach ($technologyQueries as $query) {
            self::assertStringContainsString('LEFT JOIN app_technology_group_translation ', $query['sql']);
            self::assertStringContainsString('LEFT JOIN app_technology_item ', $query['sql']);
            self::assertStringContainsString('highlighted', $query['sql']);
            self::assertStringContainsString('locale', $query['sql']);
            self::assertSame(2, substr_count($query['sql'], 'position'));
        }
    }

    private function persistTechnologiesPage(): void
    {
        $page = new Page();
        $page->setPosition(1);
        $page->setPublished(true);
        $page->setContentTemplate('technologies.html.twig');

        foreach ([LocaleEnum::EN, LocaleEnum::PL] as $locale) {
            $translation = new PageTranslation();
            $translation->setLocale($locale);
            $translation->setSlug('technologies-page');
            $translation->setTitle('Technologies');
            $page->addTranslation($translation);
        }

        $this->entityManager->persist($page);
        $this->entityManager->flush();
    }

    /**
     * @param array{?string, ?string}           $translations
     * @param list<array{string, ?string, int}> $items
     */
    private function persistGroup(
        int $position,
        bool $highlighted,
        array $translations,
        array $items,
    ): void {
        $group = new TechnologyGroup();
        $group->setPosition($position);
        $group->setHighlighted($highlighted);

        foreach ([LocaleEnum::EN, LocaleEnum::PL] as $index => $locale) {
            if ($translations[$index] === null) {
                continue;
            }

            $translation = new TechnologyGroupTranslation();
            $translation->setLocale($locale);
            $translation->setName($translations[$index]);
            $group->addTranslation($translation);
        }

        foreach ($items as [$name, $icon, $itemPosition]) {
            $item = new TechnologyItem();
            $item->setName($name);
            $item->setIcon($icon);
            $item->setPosition($itemPosition);
            $group->addItem($item);
        }

        $this->entityManager->persist($group);
        $this->entityManager->flush();
    }

    private function requestPage(string $uri): Crawler
    {
        return $this->client->request(
            'GET',
            $uri,
            server: ['HTTP_TURBO_FRAME' => TurboFrame::PAGE_ID],
        );
    }

    /**
     * @return list<string>
     */
    private function texts(Crawler $crawler): array
    {
        return $crawler->each(static fn (Crawler $node): string => trim($node->text()));
    }
}
