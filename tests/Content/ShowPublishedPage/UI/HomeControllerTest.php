<?php

declare(strict_types=1);

namespace App\Tests\Content\ShowPublishedPage\UI;

use App\Content\Shared\Infrastructure\Persistence\Doctrine\Entity\BlockCollection;
use App\Content\Shared\Infrastructure\Persistence\Doctrine\Entity\Page;
use App\Content\Shared\Infrastructure\Persistence\Doctrine\Entity\PageTranslation;
use App\Shared\Domain\LocaleEnum;
use App\Shared\UI\TurboFrame;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 *
 * @covers \App\Content\ShowPublishedPage\UI\HomeController
 */
final class HomeControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = self::createClient();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $this->entityManager = $entityManager;

        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        new SchemaTool($this->entityManager)->createSchema($metadata);

        $this->createLayoutCollections();
    }

    public function test_root_redirects_to_default_locale(): void
    {
        $this->client->request('GET', '/');

        self::assertResponseRedirects('/en');
    }

    public function test_published_page_is_displayed_by_slug(): void
    {
        $this->createPage();

        $this->client->request('GET', '/en/characteristic-page');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString(
            '<!DOCTYPE html>',
            (string) $this->client->getResponse()->getContent(),
        );
        self::assertSelectorExists('html[lang="en"]');
        self::assertSelectorExists(sprintf(
            'turbo-frame#%s[src="/en/characteristic-page"]',
            TurboFrame::PAGE_ID,
        ));
    }

    public function test_default_page_is_displayed_without_slug(): void
    {
        $this->createPage(position: 1, englishSlug: 'not-the-default-page');
        $this->createPage(position: 3);

        $this->client->request('GET', '/en');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists(sprintf(
            'turbo-frame#%s[src="/en/characteristic-page"]',
            TurboFrame::PAGE_ID,
        ));
    }

    public function test_missing_page_returns_not_found(): void
    {
        $this->client->request('GET', '/en/missing-page');

        self::assertResponseStatusCodeSame(404);
    }

    public function test_unpublished_page_returns_not_found(): void
    {
        $this->createPage(published: false);

        $this->client->request('GET', '/en/characteristic-page');

        self::assertResponseStatusCodeSame(404);
    }

    public function test_turbo_frame_request_renders_only_page_content(): void
    {
        $this->createPage();

        $this->client->request(
            'GET',
            '/en/characteristic-page',
            server: ['HTTP_TURBO_FRAME' => TurboFrame::PAGE_ID],
        );

        self::assertResponseIsSuccessful();
        self::assertStringNotContainsString(
            '<!DOCTYPE html>',
            (string) $this->client->getResponse()->getContent(),
        );
        self::assertSelectorTextContains('h2', 'Characteristic page');
        self::assertSelectorExists(sprintf('turbo-frame#%s', TurboFrame::PAGE_ID));
    }

    /**
     * @param non-empty-string $locale
     * @param non-empty-string $slug
     * @param non-empty-string $expectedTitle
     */
    #[DataProvider('supportedLocaleProvider')]
    public function test_page_is_rendered_for_supported_locale(
        string $locale,
        string $slug,
        string $expectedTitle,
    ): void {
        $this->createPage();

        $this->client->request(
            'GET',
            sprintf('/%s/%s', $locale, $slug),
            server: ['HTTP_TURBO_FRAME' => TurboFrame::PAGE_ID],
        );

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h2', $expectedTitle);
    }

    /**
     * @return iterable<string, array{non-empty-string, non-empty-string, non-empty-string}>
     */
    public static function supportedLocaleProvider(): iterable
    {
        yield 'English' => ['en', 'characteristic-page', 'Characteristic page'];

        yield 'Polish' => ['pl', 'strona-charakterystyki', 'Strona charakterystyki'];
    }

    public function test_slug_lookup_is_not_limited_to_request_locale(): void
    {
        $this->createPage();

        $this->client->request(
            'GET',
            '/pl/characteristic-page',
            server: ['HTTP_TURBO_FRAME' => TurboFrame::PAGE_ID],
        );

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h2', 'Strona charakterystyki');
    }

    public function test_unsupported_locale_returns_not_found(): void
    {
        $this->createPage();

        $this->client->request('GET', '/de/characteristic-page');

        self::assertResponseStatusCodeSame(404);
    }

    private function createLayoutCollections(): void
    {
        foreach (['header', 'footer'] as $name) {
            $collection = new BlockCollection();
            $collection->setName($name);
            $this->entityManager->persist($collection);
        }

        $this->entityManager->flush();
    }

    private function createPage(
        bool $published = true,
        int $position = 1,
        string $englishSlug = 'characteristic-page',
    ): void {
        $page = new Page();
        $page->setPublished($published);
        $page->setPosition($position);
        $page->setContentTemplate('default.html.twig');

        $englishTranslation = new PageTranslation();
        $englishTranslation->setLocale(LocaleEnum::EN);
        $englishTranslation->setTitle('Characteristic page');
        $englishTranslation->setSlug($englishSlug);
        $englishTranslation->setMenuTitle('Characteristic page');
        $page->addTranslation($englishTranslation);

        $polishTranslation = new PageTranslation();
        $polishTranslation->setLocale(LocaleEnum::PL);
        $polishTranslation->setTitle('Strona charakterystyki');
        $polishTranslation->setSlug('strona-charakterystyki');
        $polishTranslation->setMenuTitle('Strona charakterystyki');
        $page->addTranslation($polishTranslation);

        $this->entityManager->persist($page);
        $this->entityManager->flush();
    }
}
