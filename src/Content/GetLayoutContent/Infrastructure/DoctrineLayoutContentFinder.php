<?php

declare(strict_types=1);

namespace App\Content\GetLayoutContent\Infrastructure;

use App\Content\GetLayoutContent\Application\LayoutContentBlockView;
use App\Content\GetLayoutContent\Application\LayoutContentFinder;
use App\Content\GetLayoutContent\Application\LayoutContentView;
use App\Content\Shared\Infrastructure\Persistence\Doctrine\Entity\BlockCollection;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineLayoutContentFinder implements LayoutContentFinder
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    public function find(string $collectionName, string $locale): ?LayoutContentView
    {
        /**
         * @var list<array{
         *     collectionName: string,
         *     blockName: ?string,
         *     blockPosition: ?int,
         *     blockContent: ?string,
         *     blockImage: ?string
         * }> $rows
         */
        $rows = $this->entityManager->createQueryBuilder()
            ->select('collection.name AS collectionName')
            ->addSelect('block.name AS blockName')
            ->addSelect('block.position AS blockPosition')
            ->addSelect('translation.content AS blockContent')
            ->addSelect('translation.image AS blockImage')
            ->from(BlockCollection::class, 'collection')
            ->leftJoin('collection.blocks', 'block')
            ->leftJoin(
                'block.translations',
                'translation',
                'WITH',
                'translation.locale = :locale',
            )
            ->where('collection.name = :collectionName')
            ->setParameter('collectionName', $collectionName)
            ->setParameter('locale', $locale)
            ->orderBy('block.position', \SortDirection::Ascending)
            ->getQuery()
            ->getArrayResult()
        ;

        if ($rows === []) {
            return null;
        }

        $blocks = [];

        foreach ($rows as $row) {
            if ($row['blockName'] === null) {
                continue;
            }

            $blocks[] = new LayoutContentBlockView(
                $row['blockName'],
                $row['blockContent'],
                $row['blockImage'],
            );
        }

        return new LayoutContentView($rows[0]['collectionName'], $blocks);
    }
}
