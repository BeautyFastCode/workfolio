<?php

declare(strict_types=1);

namespace App\Tests\Portfolio\ListTechnologies\Infrastructure;

use App\Portfolio\ListTechnologies\Application\TechnologyGroupView;
use App\Portfolio\ListTechnologies\Application\TechnologyItemView;
use App\Portfolio\ListTechnologies\Infrastructure\DoctrineTechnologyFinder;
use App\Portfolio\Shared\Infrastructure\Persistence\Doctrine\Entity\TechnologyGroup;
use App\Portfolio\Shared\Infrastructure\Persistence\Doctrine\Entity\TechnologyGroupTranslation;
use App\Portfolio\Shared\Infrastructure\Persistence\Doctrine\Entity\TechnologyItem;
use App\Shared\Domain\LocaleEnum;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bridge\Doctrine\Middleware\Debug\DebugDataHolder;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @internal
 *
 * @covers \App\Portfolio\ListTechnologies\Infrastructure\DoctrineTechnologyFinder
 */
final class DoctrineTechnologyFinderTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private DoctrineTechnologyFinder $finder;

    protected function setUp(): void
    {
        self::bootKernel(['debug' => true]);

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $this->entityManager = $entityManager;
        new SchemaTool($entityManager)->createSchema($entityManager->getMetadataFactory()->getAllMetadata());
        $this->finder = new DoctrineTechnologyFinder($entityManager);
    }

    public function test_it_returns_an_empty_list(): void
    {
        self::assertSame([], $this->finder->find('en', true));
    }

    public function test_it_filters_and_orders_groups_and_items_for_both_locales(): void
    {
        $this->persistGroup(3, false, ['Other', 'Pozostałe'], [['Hidden', null, 1]]);
        $this->persistGroup(2, true, ['Backend', 'Zaplecze'], [
            ['Symfony', 'symfony-icon', 2],
            ['PHP', null, 1],
        ]);
        $this->persistGroup(1, true, ['Frontend', 'Interfejs'], [['Twig', 'twig-icon', 1]]);

        self::assertEquals([
            new TechnologyGroupView('Frontend', [new TechnologyItemView('Twig', 'twig-icon')]),
            new TechnologyGroupView('Backend', [
                new TechnologyItemView('PHP', null),
                new TechnologyItemView('Symfony', 'symfony-icon'),
            ]),
        ], $this->finder->find('en', true));
        self::assertEquals([
            new TechnologyGroupView('Interfejs', [new TechnologyItemView('Twig', 'twig-icon')]),
            new TechnologyGroupView('Zaplecze', [
                new TechnologyItemView('PHP', null),
                new TechnologyItemView('Symfony', 'symfony-icon'),
            ]),
        ], $this->finder->find('pl', true));
        self::assertEquals([
            new TechnologyGroupView('Other', [new TechnologyItemView('Hidden', null)]),
        ], $this->finder->find('en', false));
    }

    public function test_it_keeps_empty_groups_and_missing_translations_without_fallback(): void
    {
        $this->persistGroup(1, true, [null, 'Tylko polski'], []);

        self::assertEquals([
            new TechnologyGroupView(null, []),
        ], $this->finder->find('en', true));
    }

    public function test_it_executes_one_filtered_locale_and_position_ordered_query(): void
    {
        $this->persistGroup(1, true, ['Backend', 'Zaplecze'], [['PHP', null, 1]]);
        $debugDataHolder = self::getContainer()->get('doctrine.debug_data_holder');
        self::assertInstanceOf(DebugDataHolder::class, $debugDataHolder);
        $debugDataHolder->reset();

        $this->finder->find('pl', true);

        /** @var array<string, list<array{sql: string}>> $queryData */
        $queryData = $debugDataHolder->getData();
        $queries = [];

        foreach ($queryData as $connectionQueries) {
            array_push($queries, ...$connectionQueries);
        }

        self::assertCount(1, $queries);
        $sql = $queries[0]['sql'];
        self::assertStringContainsString('FROM app_technology_group ', $sql);
        self::assertStringContainsString('LEFT JOIN app_technology_group_translation ', $sql);
        self::assertStringContainsString('LEFT JOIN app_technology_item ', $sql);
        self::assertStringContainsString('highlighted', $sql);
        self::assertStringContainsString('locale', $sql);
        self::assertSame(2, substr_count($sql, 'position'));
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
}
