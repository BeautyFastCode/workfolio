<?php

declare(strict_types=1);

namespace App\Contact\ListContactLinks\Infrastructure;

use App\Contact\ListContactLinks\Application\ContactLinkFinder;
use App\Contact\ListContactLinks\Application\ContactLinkView;
use App\Contact\Shared\Infrastructure\Persistence\Doctrine\Entity\ContactLink;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineContactLinkFinder implements ContactLinkFinder
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    public function findEnabled(): array
    {
        /**
         * @var list<array{
         *     name: string,
         *     href: string,
         *     icon: ?string
         * }> $rows
         */
        $rows = $this->entityManager->createQueryBuilder()
            ->select('contactLink.name AS name')
            ->addSelect('contactLink.href AS href')
            ->addSelect('contactLink.icon AS icon')
            ->from(ContactLink::class, 'contactLink')
            ->where('contactLink.enabled = :enabled')
            ->setParameter('enabled', true)
            ->orderBy('contactLink.position', \SortDirection::Ascending)
            ->addOrderBy('contactLink.id', \SortDirection::Ascending)
            ->getQuery()
            ->getArrayResult()
        ;

        return array_map(
            static fn (array $row): ContactLinkView => new ContactLinkView(
                $row['name'],
                $row['href'],
                $row['icon'],
            ),
            $rows,
        );
    }
}
