<?php

declare(strict_types=1);

namespace App\Tests\Contact\ListContactLinks\Infrastructure;

use App\Contact\ListContactLinks\Application\ContactLinkView;
use App\Contact\ListContactLinks\Infrastructure\DoctrineContactLinkFinder;
use App\Contact\Shared\Infrastructure\Persistence\Doctrine\Entity\ContactLink;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bridge\Doctrine\Middleware\Debug\DebugDataHolder;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @internal
 *
 * @covers \App\Contact\ListContactLinks\Infrastructure\DoctrineContactLinkFinder
 */
final class DoctrineContactLinkFinderTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private DoctrineContactLinkFinder $finder;

    protected function setUp(): void
    {
        self::bootKernel(['debug' => true]);

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $this->entityManager = $entityManager;
        new SchemaTool($entityManager)->createSchema($entityManager->getMetadataFactory()->getAllMetadata());
        $this->finder = new DoctrineContactLinkFinder($entityManager);
    }

    public function test_it_returns_an_empty_list(): void
    {
        self::assertSame([], $this->finder->findEnabled());
    }

    public function test_it_returns_only_enabled_links_ordered_by_position(): void
    {
        $this->persistLink('LinkedIn', 'https://linkedin.example', 'fa-linkedin', 3, true);
        $this->persistLink('Hidden', 'https://hidden.example', 'fa-hidden', 1, false);
        $this->persistLink('E-mail', 'mailto:office@example.com', null, 1, true);
        $this->persistLink('GitHub', 'https://github.example', 'fa-github', 2, true);

        self::assertEquals([
            new ContactLinkView('E-mail', 'mailto:office@example.com', null),
            new ContactLinkView('GitHub', 'https://github.example', 'fa-github'),
            new ContactLinkView('LinkedIn', 'https://linkedin.example', 'fa-linkedin'),
        ], $this->finder->findEnabled());
    }

    public function test_it_executes_one_filtered_and_stably_ordered_projection_query(): void
    {
        $this->persistLink('GitHub', 'https://github.example', null, 1, true);
        $debugDataHolder = self::getContainer()->get('doctrine.debug_data_holder');
        self::assertInstanceOf(DebugDataHolder::class, $debugDataHolder);
        $debugDataHolder->reset();

        $this->finder->findEnabled();

        /** @var array<string, list<array{sql: string}>> $queryData */
        $queryData = $debugDataHolder->getData();
        $queries = [];

        foreach ($queryData as $connectionQueries) {
            array_push($queries, ...$connectionQueries);
        }

        self::assertCount(1, $queries);
        $sql = $queries[0]['sql'];
        self::assertStringContainsString('FROM app_contact_link ', $sql);
        self::assertStringContainsString('enabled', $sql);
        self::assertStringContainsString('ORDER BY', $sql);
        self::assertStringContainsString('position', $sql);
        self::assertStringContainsString('id', $sql);
    }

    private function persistLink(
        string $name,
        string $href,
        ?string $icon,
        int $position,
        bool $enabled,
    ): void {
        $link = new ContactLink();
        $link->setName($name);
        $link->setHref($href);
        $link->setIcon($icon);
        $link->setPosition($position);
        $link->setEnabled($enabled);
        $this->entityManager->persist($link);
        $this->entityManager->flush();
    }
}
