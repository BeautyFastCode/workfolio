<?php

declare(strict_types=1);

namespace App\Portfolio\ListTechnologies\Infrastructure;

use App\Portfolio\ListTechnologies\Application\TechnologyFinder;
use App\Portfolio\ListTechnologies\Application\TechnologyGroupView;
use App\Portfolio\ListTechnologies\Application\TechnologyItemView;
use App\Portfolio\Shared\Infrastructure\Persistence\Doctrine\Entity\TechnologyGroup;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineTechnologyFinder implements TechnologyFinder
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    public function find(string $locale, bool $highlighted): array
    {
        /**
         * @var list<array{
         *     groupId: string|\Stringable,
         *     groupName: ?string,
         *     itemName: ?string,
         *     itemIcon: ?string
         * }> $rows
         */
        $rows = $this->entityManager->createQueryBuilder()
            ->select('technologyGroup.id AS groupId')
            ->addSelect('translation.name AS groupName')
            ->addSelect('item.name AS itemName')
            ->addSelect('item.icon AS itemIcon')
            ->from(TechnologyGroup::class, 'technologyGroup')
            ->leftJoin(
                'technologyGroup.translations',
                'translation',
                'WITH',
                'translation.locale = :locale',
            )
            ->leftJoin('technologyGroup.items', 'item')
            ->where('technologyGroup.highlighted = :highlighted')
            ->setParameter('locale', $locale)
            ->setParameter('highlighted', $highlighted)
            ->orderBy('technologyGroup.position', \SortDirection::Ascending)
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
                $groups[] = new TechnologyGroupView($currentName, $currentItems);
                $currentItems = [];
            }

            if ($currentGroupId !== $groupId) {
                $currentGroupId = $groupId;
                $currentName = $row['groupName'];
            }

            if ($row['itemName'] !== null) {
                $currentItems[] = new TechnologyItemView($row['itemName'], $row['itemIcon']);
            }
        }

        if ($currentGroupId !== null) {
            $groups[] = new TechnologyGroupView($currentName, $currentItems);
        }

        return $groups;
    }
}
