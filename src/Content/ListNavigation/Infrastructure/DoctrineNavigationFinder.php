<?php

declare(strict_types=1);

namespace App\Content\ListNavigation\Infrastructure;

use App\Content\ListNavigation\Application\NavigationCandidate;
use App\Content\ListNavigation\Application\NavigationFinder;
use App\Content\Shared\Infrastructure\Persistence\Doctrine\Entity\Page;
use App\Shared\Domain\LocaleEnum;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineNavigationFinder implements NavigationFinder
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    public function findPublished(string $locale): array
    {
        /** @var list<Page> $pages */
        $pages = $this->entityManager->createQueryBuilder()
            ->select('p')
            ->addSelect('t')
            ->from(Page::class, 'p')
            ->leftJoin('p.translations', 't')
            ->where('p.published = :published')
            ->setParameter('published', true)
            ->orderBy('p.position', \SortDirection::Ascending)
            ->getQuery()
            ->getResult()
        ;

        $localeEnum = LocaleEnum::from($locale);
        $items = [];

        foreach ($pages as $page) {
            $routeSlugs = [];

            foreach ($page->getTranslations() as $translation) {
                $routeSlugs[] = $translation->getSlug();
            }

            $items[] = new NavigationCandidate(
                $page->getSlug($localeEnum),
                $page->getMenuTitle($localeEnum),
                $page->getPosition(),
                $routeSlugs,
            );
        }

        return $items;
    }
}
