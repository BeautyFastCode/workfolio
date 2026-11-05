<?php

declare(strict_types=1);

namespace App\Tests\Portfolio\ListStatistics\Infrastructure;

use App\Portfolio\ListStatistics\Application\StatisticGroupView;
use App\Portfolio\ListStatistics\Application\StatisticItemView;
use App\Portfolio\ListStatistics\Infrastructure\DoctrineStatisticFinder;
use App\Portfolio\Shared\Infrastructure\Persistence\Doctrine\Entity\StatisticGroup;
use App\Portfolio\Shared\Infrastructure\Persistence\Doctrine\Entity\StatisticGroupTranslation;
use App\Portfolio\Shared\Infrastructure\Persistence\Doctrine\Entity\StatisticItem;
use App\Portfolio\Shared\Infrastructure\Persistence\Doctrine\Entity\StatisticItemTranslation;
use App\Shared\Domain\LocaleEnum;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bridge\Doctrine\Middleware\Debug\DebugDataHolder;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @internal
 *
 * @covers \App\Portfolio\ListStatistics\Infrastructure\DoctrineStatisticFinder
 */
final class DoctrineStatisticFinderTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private DoctrineStatisticFinder $finder;

    protected function setUp(): void
    {
        self::bootKernel(['debug' => true]);

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $this->entityManager = $entityManager;
        new SchemaTool($entityManager)->createSchema($entityManager->getMetadataFactory()->getAllMetadata());
        $this->finder = new DoctrineStatisticFinder($entityManager);
    }

    public function test_it_returns_an_empty_list(): void
    {
        self::assertSame([], $this->finder->findAll('en'));
    }

    public function test_it_orders_groups_and_items_and_selects_both_locales(): void
    {
        $this->persistGroup(2, ['Growth', 'Wzrost'], [
            [2, 'Revenue', 'Przychody', '150%', 'fa-chart'],
            [1, 'Partners', 'Partnerzy', '85+', null],
        ]);
        $this->persistGroup(1, ['Achievements', 'Osiągnięcia'], [
            [1, 'Projects', 'Projekty', '300', 'fa-code'],
        ]);

        self::assertEquals([
            new StatisticGroupView('Achievements', [
                new StatisticItemView('Projects', 'fa-code', '300'),
            ]),
            new StatisticGroupView('Growth', [
                new StatisticItemView('Partners', null, '85+'),
                new StatisticItemView('Revenue', 'fa-chart', '150%'),
            ]),
        ], $this->finder->findAll('en'));
        self::assertEquals([
            new StatisticGroupView('Osiągnięcia', [
                new StatisticItemView('Projekty', 'fa-code', '300'),
            ]),
            new StatisticGroupView('Wzrost', [
                new StatisticItemView('Partnerzy', null, '85+'),
                new StatisticItemView('Przychody', 'fa-chart', '150%'),
            ]),
        ], $this->finder->findAll('pl'));
    }

    public function test_it_keeps_empty_groups_and_missing_translations_without_fallback(): void
    {
        $this->persistGroup(1, [null, 'Tylko polski'], []);
        $this->persistGroup(2, ['Only English', null], [
            [1, null, 'Tylko polski element', '7', null],
        ]);

        self::assertEquals([
            new StatisticGroupView(null, []),
            new StatisticGroupView('Only English', [
                new StatisticItemView(null, null, '7'),
            ]),
        ], $this->finder->findAll('en'));
    }

    public function test_it_executes_one_locale_filtered_and_position_ordered_query(): void
    {
        $this->persistGroup(1, ['Achievements', 'Osiągnięcia'], [
            [1, 'Projects', 'Projekty', '300', null],
        ]);
        $debugDataHolder = self::getContainer()->get('doctrine.debug_data_holder');
        self::assertInstanceOf(DebugDataHolder::class, $debugDataHolder);
        $debugDataHolder->reset();

        $this->finder->findAll('pl');

        /** @var array<string, list<array{sql: string}>> $queryData */
        $queryData = $debugDataHolder->getData();
        $queries = [];

        foreach ($queryData as $connectionQueries) {
            array_push($queries, ...$connectionQueries);
        }

        self::assertCount(1, $queries);
        $sql = $queries[0]['sql'];
        self::assertStringContainsString('FROM app_statistic_group ', $sql);
        self::assertStringContainsString('LEFT JOIN app_statistic_group_translation ', $sql);
        self::assertStringContainsString('LEFT JOIN app_statistic_item ', $sql);
        self::assertStringContainsString('LEFT JOIN app_statistic_item_translation ', $sql);
        self::assertSame(2, substr_count($sql, 'locale'));
        self::assertSame(2, substr_count($sql, 'position'));
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
}
