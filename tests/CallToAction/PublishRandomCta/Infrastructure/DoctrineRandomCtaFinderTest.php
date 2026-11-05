<?php

declare(strict_types=1);

namespace App\Tests\CallToAction\PublishRandomCta\Infrastructure;

use App\CallToAction\PublishRandomCta\Application\RandomCta;
use App\CallToAction\PublishRandomCta\Application\RandomCtaTranslation;
use App\CallToAction\PublishRandomCta\Infrastructure\DoctrineRandomCtaFinder;
use App\CallToAction\Shared\Infrastructure\Persistence\Doctrine\Entity\CtaMessage;
use App\CallToAction\Shared\Infrastructure\Persistence\Doctrine\Entity\CtaMessageTranslation;
use App\Shared\Domain\LocaleEnum;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @internal
 *
 * @covers \App\CallToAction\PublishRandomCta\Infrastructure\DoctrineRandomCtaFinder
 */
final class DoctrineRandomCtaFinderTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private DoctrineRandomCtaFinder $finder;

    protected function setUp(): void
    {
        self::bootKernel();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $this->entityManager = $entityManager;
        new SchemaTool($entityManager)->createSchema($entityManager->getMetadataFactory()->getAllMetadata());

        $this->finder = new DoctrineRandomCtaFinder($entityManager);
    }

    public function test_it_returns_null_for_an_empty_data_set(): void
    {
        self::assertNull($this->finder->findRandom());
    }

    public function test_it_maps_the_selected_entity_without_exposing_doctrine_and_preserves_translation_order(): void
    {
        $ctaMessage = new CtaMessage();
        $english = new CtaMessageTranslation();
        $english->setLocale(LocaleEnum::EN);
        $english->setMessage('Doctrine CTA in English.');
        $ctaMessage->addTranslation($english);
        $polish = new CtaMessageTranslation();
        $polish->setLocale(LocaleEnum::PL);
        $polish->setMessage('Doctrine CTA po polsku.');
        $ctaMessage->addTranslation($polish);
        $this->entityManager->persist($ctaMessage);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $result = $this->finder->findRandom();

        self::assertEquals(new RandomCta('Doctrine CTA in English.', [
            new RandomCtaTranslation('en', 'Doctrine CTA in English.'),
            new RandomCtaTranslation('pl', 'Doctrine CTA po polsku.'),
        ]), $result);
    }
}
