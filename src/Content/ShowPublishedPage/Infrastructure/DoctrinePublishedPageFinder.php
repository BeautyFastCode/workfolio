<?php

declare(strict_types=1);

namespace App\Content\ShowPublishedPage\Infrastructure;

use App\Content\Shared\Infrastructure\Persistence\Doctrine\Entity\Page;
use App\Content\Shared\Infrastructure\Persistence\Doctrine\Entity\PageBlock;
use App\Content\ShowPublishedPage\Application\DefaultPageSelection;
use App\Content\ShowPublishedPage\Application\PublishedPageBlockView;
use App\Content\ShowPublishedPage\Application\PublishedPageFinder;
use App\Content\ShowPublishedPage\Application\PublishedPageView;
use App\Shared\Domain\LocaleEnum;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

final readonly class DoctrinePublishedPageFinder implements PublishedPageFinder
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    public function findPublishedBySlug(string $slug, string $locale): ?PublishedPageView
    {
        $queryBuilder = $this->publishedPageQuery()
            ->innerJoin('p.translations', 'slug_translation')
            ->andWhere('slug_translation.slug = :slug')
            ->setParameter('slug', $slug)
        ;

        return $this->toView($this->oneOrNull($queryBuilder), $locale);
    }

    public function findDefaultPublished(
        DefaultPageSelection $selection,
        string $locale,
    ): ?PublishedPageView {
        $queryBuilder = $this->publishedPageQuery()
            ->andWhere('p.position = :position')
            ->setParameter('position', $selection->position)
        ;

        return $this->toView($this->oneOrNull($queryBuilder), $locale);
    }

    private function publishedPageQuery(): QueryBuilder
    {
        return $this->entityManager->createQueryBuilder()
            ->select('p')
            ->addSelect('t, b, bt')
            ->from(Page::class, 'p')
            ->leftJoin('p.translations', 't')
            ->leftJoin('p.blocks', 'b')
            ->leftJoin('b.translations', 'bt')
            ->where('p.published = :published')
            ->setParameter('published', true)
        ;
    }

    private function oneOrNull(QueryBuilder $queryBuilder): ?Page
    {
        $result = $queryBuilder->getQuery()->getOneOrNullResult();

        if ($result !== null && !$result instanceof Page) {
            throw new \UnexpectedValueException(sprintf(
                'Expected %s|null, got %s.',
                Page::class,
                get_debug_type($result),
            ));
        }

        return $result;
    }

    private function toView(?Page $page, string $locale): ?PublishedPageView
    {
        if (!$page instanceof Page) {
            return null;
        }

        $localeEnum = LocaleEnum::from($locale);
        $blocks = [];

        /** @var PageBlock $block */
        foreach ($page->getBlocks() as $block) {
            $blocks[] = new PublishedPageBlockView(
                $block->getName(),
                $block->getType()->value,
                $block->getContent($localeEnum),
            );
        }

        return new PublishedPageView(
            $page->getSlug($localeEnum),
            $page->getTitle($localeEnum),
            $page->getMenuTitle($localeEnum),
            $page->getContentTemplate(),
            $blocks,
        );
    }
}
