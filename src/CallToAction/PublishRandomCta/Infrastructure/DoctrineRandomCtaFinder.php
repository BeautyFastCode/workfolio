<?php

declare(strict_types=1);

namespace App\CallToAction\PublishRandomCta\Infrastructure;

use App\CallToAction\PublishRandomCta\Application\RandomCta;
use App\CallToAction\PublishRandomCta\Application\RandomCtaFinder;
use App\CallToAction\PublishRandomCta\Application\RandomCtaTranslation;
use App\CallToAction\Shared\Infrastructure\Persistence\Doctrine\Entity\CtaMessage;
use App\CallToAction\Shared\Infrastructure\Persistence\Doctrine\Entity\CtaMessageTranslation;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineRandomCtaFinder implements RandomCtaFinder
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    public function findRandom(): ?RandomCta
    {
        $count = (int) $this->entityManager
            ->createQueryBuilder()
            ->select('COUNT(cta.id)')
            ->from(CtaMessage::class, 'cta')
            ->getQuery()
            ->getSingleScalarResult()
        ;

        if ($count === 0) {
            return null;
        }

        $id = $this->entityManager
            ->createQueryBuilder()
            ->select('cta.id')
            ->from(CtaMessage::class, 'cta')
            ->orderBy('cta.id', \SortDirection::Ascending)
            ->setFirstResult(random_int(0, $count - 1))
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        $ctaMessage = $this->entityManager
            ->createQueryBuilder()
            ->select('cta', 'translation')
            ->from(CtaMessage::class, 'cta')
            ->leftJoin('cta.translations', 'translation')
            ->where('cta.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        if (!$ctaMessage instanceof CtaMessage) {
            return null;
        }

        $translations = [];

        /** @var CtaMessageTranslation $translation */
        foreach ($ctaMessage->getTranslations() as $translation) {
            $translations[] = new RandomCtaTranslation(
                $translation->getLocale()->value,
                $translation->getMessage(),
            );
        }

        return new RandomCta((string) $ctaMessage, $translations);
    }
}
