<?php

declare(strict_types=1);

namespace App\Tests\Portfolio\ListStatistics\UI;

use App\Content\Shared\Infrastructure\Persistence\Doctrine\Entity\BlockCollection;
use App\Content\Shared\Infrastructure\Persistence\Doctrine\Entity\Page;
use App\Content\Shared\Infrastructure\Persistence\Doctrine\Entity\PageTranslation;
use App\Portfolio\ListStatistics\Application\ListStatisticsHandler;
use App\Portfolio\ListStatistics\Application\StatisticFinder;
use App\Portfolio\ListStatistics\Application\StatisticGroupView;
use App\Portfolio\ListStatistics\Application\StatisticItemView;
use App\Portfolio\ListStatistics\UI\Statistics;
use App\Portfolio\Shared\Infrastructure\Persistence\Doctrine\Entity\StatisticGroup;
use App\Portfolio\Shared\Infrastructure\Persistence\Doctrine\Entity\StatisticGroupTranslation;
use App\Portfolio\Shared\Infrastructure\Persistence\Doctrine\Entity\StatisticItem;
use App\Portfolio\Shared\Infrastructure\Persistence\Doctrine\Entity\StatisticItemTranslation;
use App\Shared\Domain\LocaleEnum;
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
 * @covers \App\Portfolio\ListStatistics\UI\Statistics
 */
final class StatisticsTest extends WebTestCase
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

        $this->persistPage();
    }

    public function test_component_is_a_thin_adapter_preserving_its_locale_input(): void
    {
        $groups = [new StatisticGroupView('Osiągnięcia', [
            new StatisticItemView('Projekty', null, '300'),
        ])];
        $finder = $this->createMock(StatisticFinder::class);
        $finder
            ->expects(self::once())
            ->method('findAll')
            ->with('pl')
            ->willReturn($groups)
        ;
        $component = new Statistics(new ListStatisticsHandler($finder));
        $component->locale = LocaleEnum::PL;

        $component->postMount();

        self::assertSame($groups, $component->data);
    }

    public function test_html_preserves_order_both_locales_missing_translations_and_null_icons(): void
    {
        $this->persistGroup(2, ['Growth', 'Wzrost'], [
            [2, 'Revenue', 'Przychody', '150%', 'fa-chart'],
            [1, 'Partners', 'Partnerzy', '85+', null],
        ]);
        $this->persistGroup(1, [null, 'Osiągnięcia'], [
            [1, null, 'Projekty', '300', 'fa-code'],
        ]);

        $english = $this->requestPage('/en/statistics-page');

        self::assertResponseIsSuccessful();
        self::assertSame(['', 'Growth'], $this->texts($english->filter('.statistics .mt-5 > .text-center p')));
        self::assertSame(['300', '85+', '150%'], $this->texts($english->filter('.statistics .value')));
        self::assertSame(['', 'Partners', 'Revenue'], $this->texts($english->filter('.statistics .label')));
        self::assertCount(3, $english->filter('.statistics-card i'));
        self::assertCount(1, $english->filter('.statistics-card i[class=" text-accent mb-0"]'));
        self::assertCount(1, $english->filter('.statistics-card i.fa-code'));
        self::assertCount(1, $english->filter('.statistics-card i.fa-chart'));

        $polish = $this->requestPage('/pl/statistics-page');

        self::assertResponseIsSuccessful();
        self::assertSame(['Osiągnięcia', 'Wzrost'], $this->texts($polish->filter('.statistics .mt-5 > .text-center p')));
        self::assertSame(['Projekty', 'Partnerzy', 'Przychody'], $this->texts($polish->filter('.statistics .label')));
    }

    public function test_empty_results_render_the_existing_empty_section(): void
    {
        $crawler = $this->requestPage('/en/statistics-page');

        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('section.statistics'));
        self::assertCount(0, $crawler->filter('.statistics-card'));
    }

    public function test_full_rendering_executes_one_shaped_statistics_query(): void
    {
        $this->persistGroup(1, ['Achievements', 'Osiągnięcia'], [
            [1, 'Projects', 'Projekty', '300', null],
        ]);
        $this->client->enableProfiler();

        $this->requestPage('/en/statistics-page');

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

        $statisticQueries = array_values(array_filter(
            $queries,
            static fn (array $query): bool => str_contains($query['sql'], 'FROM app_statistic_group '),
        ));

        self::assertCount(1, $statisticQueries);
        $sql = $statisticQueries[0]['sql'];
        self::assertStringContainsString('LEFT JOIN app_statistic_group_translation ', $sql);
        self::assertStringContainsString('LEFT JOIN app_statistic_item ', $sql);
        self::assertStringContainsString('LEFT JOIN app_statistic_item_translation ', $sql);
        self::assertSame(2, substr_count($sql, 'locale'));
        self::assertSame(2, substr_count($sql, 'position'));
    }

    private function persistPage(): void
    {
        $page = new Page();
        $page->setPosition(1);
        $page->setPublished(true);
        $page->setContentTemplate('default.html.twig');

        foreach ([LocaleEnum::EN, LocaleEnum::PL] as $locale) {
            $translation = new PageTranslation();
            $translation->setLocale($locale);
            $translation->setSlug('statistics-page');
            $translation->setTitle('Statistics');
            $page->addTranslation($translation);
        }

        $this->entityManager->persist($page);
        $this->entityManager->flush();
    }

    /**
     * @param array{?string, ?string}                             $translations
     * @param list<array{int, ?string, ?string, string, ?string}> $items
     */
    private function persistGroup(int $position, array $translations, array $items): void
    {
        $group = new StatisticGroup();
        $group->setPosition($position);

        foreach ([LocaleEnum::EN, LocaleEnum::PL] as $index => $locale) {
            if ($translations[$index] === null) {
                continue;
            }

            $translation = new StatisticGroupTranslation();
            $translation->setLocale($locale);
            $translation->setName($translations[$index]);
            $group->addTranslation($translation);
        }

        foreach ($items as [$itemPosition, $english, $polish, $value, $icon]) {
            $item = new StatisticItem();
            $item->setPosition($itemPosition);
            $item->setValue($value);
            $item->setIcon($icon);

            foreach ([[LocaleEnum::EN, $english], [LocaleEnum::PL, $polish]] as [$locale, $name]) {
                if ($name === null) {
                    continue;
                }

                $translation = new StatisticItemTranslation();
                $translation->setLocale($locale);
                $translation->setName($name);
                $item->addTranslation($translation);
            }

            $group->addItem($item);
        }

        $this->entityManager->persist($group);
        $this->entityManager->flush();
    }

    private function requestPage(string $uri): Crawler
    {
        return $this->client->request('GET', $uri);
    }

    /**
     * @return list<string>
     */
    private function texts(Crawler $crawler): array
    {
        return $crawler->each(static fn (Crawler $node): string => trim($node->text()));
    }
}
