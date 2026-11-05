<?php

declare(strict_types=1);

namespace App\Portfolio\ListStatistics\Infrastructure;

use App\Portfolio\ListStatistics\Application\StatisticFinder;
use App\Portfolio\ListStatistics\Application\StatisticGroupView;
use App\Portfolio\ListStatistics\Application\StatisticItemView;
use App\Portfolio\Shared\Infrastructure\Persistence\Doctrine\Entity\StatisticGroup;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineStatisticFinder implements StatisticFinder
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    public function findAll(string $locale): array
    {
        /**
         * @var list<array{
         *     groupId: string|\Stringable,
         *     groupName: ?string,
         *     itemId: null|string|\Stringable,
         *     itemName: ?string,
         *     itemIcon: ?string,
         *     itemValue: ?string
         * }> $rows
         */
        $rows = $this->entityManager->createQueryBuilder()
            ->select('statisticGroup.id AS groupId')
            ->addSelect('groupTranslation.name AS groupName')
            ->addSelect('item.id AS itemId')
            ->addSelect('itemTranslation.name AS itemName')
            ->addSelect('item.icon AS itemIcon')
            ->addSelect('item.value AS itemValue')
            ->from(StatisticGroup::class, 'statisticGroup')
            ->leftJoin(
                'statisticGroup.translations',
                'groupTranslation',
                'WITH',
                'groupTranslation.locale = :locale',
            )
            ->leftJoin('statisticGroup.items', 'item')
            ->leftJoin(
                'item.translations',
                'itemTranslation',
                'WITH',
                'itemTranslation.locale = :locale',
            )
            ->setParameter('locale', $locale)
            ->orderBy('statisticGroup.position', \SortDirection::Ascending)
            ->addOrderBy('item.position', \SortDirection::Ascending)
            ->getQuery()
            ->getArrayResult()
        ;

        $groups = [];
        $currentGroupId = null;
        $currentName = null;
        $currentItems = [];

        foreach ($rows as $row) {
            $groupId = (string) $row['groupId'];

            if ($currentGroupId !== null && $currentGroupId !== $groupId) {
                $groups[] = new StatisticGroupView($currentName, $currentItems);
                $currentItems = [];
            }

            if ($currentGroupId !== $groupId) {
                $currentGroupId = $groupId;
                $currentName = $row['groupName'];
            }

            if ($row['itemId'] !== null) {
                $currentItems[] = new StatisticItemView(
                    $row['itemName'],
                    $row['itemIcon'],
                    (string) $row['itemValue'],
                );
            }
        }

        if ($currentGroupId !== null) {
            $groups[] = new StatisticGroupView($currentName, $currentItems);
        }

        return $groups;
    }
}
