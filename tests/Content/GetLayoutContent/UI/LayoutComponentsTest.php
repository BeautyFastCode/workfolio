<?php

declare(strict_types=1);

namespace App\Tests\Content\GetLayoutContent\UI;

use App\Content\GetLayoutContent\Application\GetLayoutContentHandler;
use App\Content\GetLayoutContent\Application\LayoutContentBlockView;
use App\Content\GetLayoutContent\Application\LayoutContentFinder;
use App\Content\GetLayoutContent\Application\LayoutContentView;
use App\Content\GetLayoutContent\UI\Footer;
use App\Content\GetLayoutContent\UI\Header;
use App\Content\Shared\Infrastructure\Persistence\Doctrine\Entity\Block;
use App\Content\Shared\Infrastructure\Persistence\Doctrine\Entity\BlockCollection;
use App\Content\Shared\Infrastructure\Persistence\Doctrine\Entity\BlockTranslation;
use App\Content\Shared\Infrastructure\Persistence\Doctrine\Entity\Page;
use App\Content\Shared\Infrastructure\Persistence\Doctrine\Entity\PageTranslation;
use App\Content\Shared\Infrastructure\Persistence\Doctrine\Enum\BlockType;
use App\Shared\Domain\LocaleEnum;
use App\Shared\UI\Twig\LocaleProvider;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bridge\Doctrine\DataCollector\DoctrineDataCollector;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Profiler\Profile;

/**
 * @internal
 *
 * @covers \App\Content\GetLayoutContent\UI\Footer
 * @covers \App\Content\GetLayoutContent\UI\Header
 */
final class LayoutComponentsTest extends WebTestCase
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
    }

    public function test_header_and_footer_are_thin_adapters_for_the_same_layout_handler(): void
    {
        $headerView = new LayoutContentView('header', [
            new LayoutContentBlockView('title', 'Header', null),
        ]);
        $footerView = new LayoutContentView('footer', [
            new LayoutContentBlockView('copyright', 'Stopka', null),
        ]);
        $finder = $this->createMock(LayoutContentFinder::class);
        $finder
            ->expects(self::exactly(2))
            ->method('find')
            ->willReturnCallback(static fn (string $name, string $locale): ?LayoutContentView => match ([$name, $locale]) {
                ['header', 'en'] => $headerView,
                ['footer', 'pl'] => $footerView,
                default => null,
            })
        ;
        $handler = new GetLayoutContentHandler($finder);

        $englishRequests = new RequestStack();
        $englishRequest = Request::create('/en');
        $englishRequest->setLocale('en');
        $englishRequests->push($englishRequest);
        $header = new Header($handler, new LocaleProvider($englishRequests));

        $polishRequests = new RequestStack();
        $polishRequest = Request::create('/pl');
        $polishRequest->setLocale('pl');
        $polishRequests->push($polishRequest);
        $footer = new Footer($handler, new LocaleProvider($polishRequests));

        $header->postMount();
        $footer->postMount();

        self::assertSame($headerView, $header->data);
        self::assertSame($footerView, $footer->data);
    }

    public function test_rendered_header_and_footer_preserve_html_and_localised_content(): void
    {
        $this->persistPage();
        $this->persistPopulatedLayout();

        $this->client->request('GET', '/en/page');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextSame('header span.tracking-wider', 'Workfolio EN');
        self::assertSelectorTextSame('header h1', 'English title');
        self::assertSelectorTextSame('header h2', 'English subtitle');
        self::assertSelectorTextSame('header p', 'English about');
        self::assertSelectorExists('header img[src="/uploads/images/portrait-en.webp"][alt="English portrait"]');
        self::assertSelectorTextContains('footer .tracking-wider', 'Copyright EN');

        $this->client->request('GET', '/pl/page');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextSame('header span.tracking-wider', 'Workfolio PL');
        self::assertSelectorTextSame('header h1', 'Polski tytuł');
        self::assertSelectorTextSame('header h2', 'Polski podtytuł');
        self::assertSelectorTextSame('header p', 'Polski opis');
        self::assertSelectorExists('header img[src="/uploads/images/portrait-pl.webp"][alt="Polski portret"]');
        self::assertSelectorTextContains('footer .tracking-wider', 'Copyright PL');
    }

    public function test_empty_collections_render_null_content_and_the_existing_image_fallback(): void
    {
        $this->persistPage();
        $this->persistEmptyCollection('header');
        $this->persistEmptyCollection('footer');

        $this->client->request('GET', '/en/page');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextSame('header h1', '');
        self::assertSelectorExists('header .image-x-fallback');
        self::assertSelectorTextSame('footer .tracking-wider', '');
    }

    public function test_full_rendering_executes_one_shaped_query_per_layout_collection(): void
    {
        $this->persistPage();
        $this->persistPopulatedLayout();
        $this->client->enableProfiler();

        $this->client->request('GET', '/en/page');

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

        $layoutQueries = array_values(array_filter(
            $queries,
            static fn (array $query): bool => str_contains($query['sql'], 'FROM app_block_collection '),
        ));

        self::assertCount(2, $layoutQueries, 'Expected one Header query and one Footer query.');

        foreach ($layoutQueries as $query) {
            self::assertStringContainsString('LEFT JOIN app_block ', $query['sql']);
            self::assertStringContainsString('LEFT JOIN app_block_translation ', $query['sql']);
            self::assertStringContainsString('ORDER BY', $query['sql']);
            self::assertStringContainsString('position', $query['sql']);
        }
    }

    private function persistPage(): void
    {
        $page = new Page();
        $page->setPosition(1);
        $page->setPublished(true);
        $page->setContentTemplate('default.html.twig');

        $english = new PageTranslation();
        $english->setLocale(LocaleEnum::EN);
        $english->setSlug('page');
        $english->setTitle('Page');
        $page->addTranslation($english);

        $polish = new PageTranslation();
        $polish->setLocale(LocaleEnum::PL);
        $polish->setSlug('strona');
        $polish->setTitle('Strona');
        $page->addTranslation($polish);

        $this->entityManager->persist($page);
        $this->entityManager->flush();
    }

    private function persistPopulatedLayout(): void
    {
        $header = $this->newCollection('header');
        $this->addBlock($header, 'about', 4, ['English about', 'Polski opis']);
        $this->addBlock($header, 'title', 2, ['English title', 'Polski tytuł']);
        $this->addBlock($header, 'site_name', 1, ['Workfolio EN', 'Workfolio PL']);
        $this->addBlock($header, 'image', 5, ['English portrait', 'Polski portret'], ['portrait-en.webp', 'portrait-pl.webp']);
        $this->addBlock($header, 'subtitle', 3, ['English subtitle', 'Polski podtytuł']);

        $footer = $this->newCollection('footer');
        $this->addBlock($footer, 'copyright', 1, ['Copyright EN', 'Copyright PL']);

        $this->entityManager->persist($header);
        $this->entityManager->persist($footer);
        $this->entityManager->flush();
    }

    private function persistEmptyCollection(string $name): void
    {
        $this->entityManager->persist($this->newCollection($name));
        $this->entityManager->flush();
    }

    private function newCollection(string $name): BlockCollection
    {
        $collection = new BlockCollection();
        $collection->setName($name);

        return $collection;
    }

    /**
     * @param array{string, string}   $content
     * @param array{?string, ?string} $images
     */
    private function addBlock(
        BlockCollection $collection,
        string $name,
        int $position,
        array $content,
        array $images = [null, null],
    ): void {
        $block = new Block();
        $block->setName($name);
        $block->setPosition($position);
        $block->setType($images === [null, null] ? BlockType::TEXT : BlockType::IMAGE);

        foreach ([LocaleEnum::EN, LocaleEnum::PL] as $index => $locale) {
            $translation = new BlockTranslation();
            $translation->setLocale($locale);
            $translation->setContent($content[$index]);
            $translation->setImage($images[$index]);
            $block->addTranslation($translation);
        }

        $collection->addBlock($block);
    }
}
