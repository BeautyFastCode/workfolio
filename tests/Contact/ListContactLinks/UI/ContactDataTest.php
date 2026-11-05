<?php

declare(strict_types=1);

namespace App\Tests\Contact\ListContactLinks\UI;

use App\Contact\ListContactLinks\Application\ContactLinkFinder;
use App\Contact\ListContactLinks\Application\ContactLinkView;
use App\Contact\ListContactLinks\Application\ListContactLinksHandler;
use App\Contact\ListContactLinks\UI\ContactData;
use App\Contact\Shared\Infrastructure\Persistence\Doctrine\Entity\ContactLink;
use App\Content\Shared\Infrastructure\Persistence\Doctrine\Entity\Page;
use App\Content\Shared\Infrastructure\Persistence\Doctrine\Entity\PageBlock;
use App\Content\Shared\Infrastructure\Persistence\Doctrine\Entity\PageBlockTranslation;
use App\Content\Shared\Infrastructure\Persistence\Doctrine\Entity\PageTranslation;
use App\Content\Shared\Infrastructure\Persistence\Doctrine\Enum\BlockType;
use App\Shared\Domain\LocaleEnum;
use App\Shared\UI\TurboFrame;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bridge\Doctrine\DataCollector\DoctrineDataCollector;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpKernel\Profiler\Profile;

/**
 * @internal
 *
 * @covers \App\Contact\ListContactLinks\UI\ContactData
 */
final class ContactDataTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = self::createClient(['debug' => true]);
        $this->client->disableReboot();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $this->entityManager = $entityManager;
        new SchemaTool($entityManager)->createSchema($entityManager->getMetadataFactory()->getAllMetadata());
        $this->persistPage();
    }

    public function test_component_is_a_thin_adapter_for_the_handler(): void
    {
        $links = [new ContactLinkView('E-mail', 'mailto:office@example.com', null)];
        $finder = $this->createMock(ContactLinkFinder::class);
        $finder
            ->expects(self::once())
            ->method('findEnabled')
            ->willReturn($links)
        ;
        $component = new ContactData(new ListContactLinksHandler($finder));

        $component->postMount();

        self::assertSame($links, $component->contacts);
    }

    public function test_html_preserves_filter_order_links_names_icons_and_null_icon(): void
    {
        $this->persistLink('LinkedIn', 'https://linkedin.example', 'fa-linkedin', 3, true);
        $this->persistLink('Hidden', 'https://hidden.example', 'fa-hidden', 1, false);
        $this->persistLink('E-mail', 'mailto:office@example.com', null, 1, true);
        $this->persistLink('GitHub', 'https://github.example', 'fa-github', 2, true);

        $crawler = $this->requestPage();

        self::assertResponseIsSuccessful();
        self::assertSame(
            ['E-mail', 'GitHub', 'LinkedIn'],
            $this->texts($crawler->filter('.contact-data a span')),
        );
        self::assertSame([
            'mailto:office@example.com',
            'https://github.example',
            'https://linkedin.example',
        ], $crawler->filter('.contact-data a')->each(
            static fn (Crawler $node): string => (string) $node->attr('href'),
        ));
        self::assertCount(0, $crawler->filter('.contact-data a[href="https://hidden.example"]'));
        self::assertCount(3, $crawler->filter('.contact-data i'));
        self::assertCount(1, $crawler->filter('.contact-data i[class=" text-accent"]'));
        self::assertCount(1, $crawler->filter('.contact-data i.fa-github'));
        self::assertCount(1, $crawler->filter('.contact-data i.fa-linkedin'));
    }

    public function test_empty_results_render_the_existing_empty_container(): void
    {
        $crawler = $this->requestPage();

        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('.contact-data'));
        self::assertCount(0, $crawler->filter('.contact-data a'));
    }

    public function test_rendering_executes_one_shaped_contact_link_query(): void
    {
        $this->persistLink('GitHub', 'https://github.example', null, 1, true);
        $this->client->enableProfiler();

        $this->requestPage();

        self::assertResponseIsSuccessful();
        $profile = $this->client->getProfile();
        self::assertInstanceOf(Profile::class, $profile);
        $collector = $profile->getCollector('db');
        self::assertInstanceOf(DoctrineDataCollector::class, $collector);

        /** @var array<string, list<array{sql: string}>> $queryData */
        $queryData = $collector->getQueries();
        $queries = [];

        foreach ($queryData as $connectionQueries) {
            array_push($queries, ...$connectionQueries);
        }

        $contactQueries = array_values(array_filter(
            $queries,
            static fn (array $query): bool => str_contains($query['sql'], 'FROM app_contact_link '),
        ));

        self::assertCount(1, $contactQueries);
        $sql = $contactQueries[0]['sql'];
        self::assertStringContainsString('enabled', $sql);
        self::assertStringContainsString('ORDER BY', $sql);
        self::assertStringContainsString('position', $sql);
        self::assertStringContainsString('id', $sql);
    }

    private function persistPage(): void
    {
        $page = new Page();
        $page->setPosition(1);
        $page->setPublished(true);
        $page->setContentTemplate('default.html.twig');

        $translation = new PageTranslation();
        $translation->setLocale(LocaleEnum::EN);
        $translation->setSlug('contact-data-page');
        $translation->setTitle('Contact data');
        $page->addTranslation($translation);

        $block = new PageBlock();
        $block->setName('contact-data');
        $block->setType(BlockType::TWIG);
        $block->setPosition(1);

        $blockTranslation = new PageBlockTranslation();
        $blockTranslation->setLocale(LocaleEnum::EN);
        $blockTranslation->setContent('[[ContactData]]');
        $block->addTranslation($blockTranslation);

        $page->addBlock($block);
        $this->entityManager->persist($page);
        $this->entityManager->flush();
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

    private function requestPage(): Crawler
    {
        return $this->client->request(
            'GET',
            '/en/contact-data-page',
            server: ['HTTP_TURBO_FRAME' => TurboFrame::PAGE_ID],
        );
    }

    /**
     * @return list<string>
     */
    private function texts(Crawler $crawler): array
    {
        return $crawler->each(static fn (Crawler $node): string => trim($node->text()));
    }
}
