<?php

declare(strict_types=1);

namespace App\Portfolio\ListTasks\Infrastructure;

use App\Portfolio\ListTasks\Application\TaskFinder;
use App\Portfolio\ListTasks\Application\TaskView;
use App\Portfolio\Shared\Infrastructure\Persistence\Doctrine\Entity\Task;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineTaskFinder implements TaskFinder
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    public function findAll(string $locale): array
    {
        /**
         * @var list<array{
         *     thumbnail: ?string,
         *     name: ?string,
         *     description: ?string,
         *     tools: ?string,
         *     impact: ?string
         * }> $rows
         */
        $rows = $this->entityManager->createQueryBuilder()
            ->select('task.thumbnail AS thumbnail')
            ->addSelect('translation.name AS name')
            ->addSelect('translation.description AS description')
            ->addSelect('translation.tools AS tools')
            ->addSelect('translation.impact AS impact')
            ->from(Task::class, 'task')
            ->leftJoin(
                'task.translations',
                'translation',
                'WITH',
                'translation.locale = :locale',
            )
            ->setParameter('locale', $locale)
            ->orderBy('task.position', \SortDirection::Ascending)
            ->getQuery()
            ->getArrayResult()
        ;

        return array_map(
            static fn (array $row): TaskView => new TaskView(
                $row['thumbnail'],
                $row['name'],
                $row['description'],
                $row['tools'],
                $row['impact'],
            ),
            $rows,
        );
    }
}
