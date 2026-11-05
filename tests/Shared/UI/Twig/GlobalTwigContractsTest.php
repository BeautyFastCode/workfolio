<?php

declare(strict_types=1);

namespace App\Tests\Shared\UI\Twig;

use App\Shared\Domain\LocaleEnum;
use App\Shared\UI\Twig\Extension\AnalyticsExtension;
use App\Shared\UI\Twig\Extension\RenderComponentExtension;
use App\Shared\UI\Twig\LocaleProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;

/**
 * @internal
 *
 * @covers \App\Shared\UI\Twig\Components\Analytics
 * @covers \App\Shared\UI\Twig\Components\ThemeSwitcher
 * @covers \App\Shared\UI\Twig\Extension\AnalyticsExtension
 * @covers \App\Shared\UI\Twig\Extension\RenderComponentExtension
 * @covers \App\Shared\UI\Twig\LocaleProvider
 */
final class GlobalTwigContractsTest extends KernelTestCase
{
    public function test_anonymous_global_components_render_from_the_shared_directory(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $twig = $container->get(Environment::class);
        $requestStack = $container->get(RequestStack::class);
        self::assertInstanceOf(Environment::class, $twig);
        self::assertInstanceOf(RequestStack::class, $requestStack);

        $request = Request::create('/en');
        $request->setLocale('en');
        $requestStack->push($request);

        $rendered = $twig->createTemplate('{{ component("GoTop") }}{{ component("LocaleSwitcher") }}')->render();

        self::assertStringContainsString('class="go-top"', $rendered);
        self::assertStringContainsString('class="locale-switcher"', $rendered);
    }

    public function test_theme_switcher_keeps_its_rendered_component_contract(): void
    {
        self::bootKernel();
        $twig = self::getContainer()->get(Environment::class);
        self::assertInstanceOf(Environment::class, $twig);

        $rendered = $twig->createTemplate('{{ component("ThemeSwitcher") }}')->render();

        self::assertStringContainsString('class="theme-switcher"', $rendered);
        self::assertStringContainsString('data-controller="theme-switcher"', $rendered);
        self::assertStringContainsString('data-action="click->theme-switcher#toggle"', $rendered);
    }

    public function test_analytics_extension_exposes_the_existing_global_shape(): void
    {
        self::assertSame([
            'analytics' => [
                'enabled' => true,
                'url' => 'https://analytics.example.test',
                'website_id' => 'website-id',
            ],
        ], new AnalyticsExtension(true, 'https://analytics.example.test', 'website-id')->getGlobals());
    }

    public function test_analytics_template_keeps_the_existing_script_contract(): void
    {
        self::bootKernel();
        $twig = self::getContainer()->get(Environment::class);
        self::assertInstanceOf(Environment::class, $twig);

        self::assertSame('', trim($twig->createTemplate('{{ component("Analytics") }}')->render()));

        $rendered = $twig->render('shared/components/Analytics.html.twig', [
            'analytics' => [
                'enabled' => true,
                'url' => 'https://analytics.example.test/script.js',
                'website_id' => 'website-id',
            ],
        ]);

        self::assertStringContainsString('src="https://analytics.example.test/script.js"', $rendered);
        self::assertStringContainsString('data-website-id="website-id"', $rendered);
        self::assertStringContainsString('data-auto-track="true"', $rendered);
        self::assertStringContainsString('data-performance="true"', $rendered);
    }

    public function test_render_component_extension_keeps_the_function_input_and_wrapper_context(): void
    {
        $twig = $this->createMock(Environment::class);
        $twig
            ->expects(self::once())
            ->method('render')
            ->with('shared/twig/_render_component_wrapper.html.twig', ['name' => 'Technologies'])
            ->willReturn('<section>technologies</section>')
        ;

        self::assertSame(
            '<section>technologies</section>',
            new RenderComponentExtension($twig)->renderComponent('[[Technologies]]'),
        );
    }

    public function test_locale_provider_returns_the_supported_request_locale(): void
    {
        $request = Request::create('/pl');
        $request->setLocale('pl');
        $requestStack = new RequestStack();
        $requestStack->push($request);

        self::assertSame(LocaleEnum::PL, new LocaleProvider($requestStack)->getCurrent());
    }

    public function test_locale_provider_falls_back_to_english_for_an_unsupported_locale(): void
    {
        $request = Request::create('/de');
        $request->setLocale('de');
        $requestStack = new RequestStack();
        $requestStack->push($request);

        self::assertSame(LocaleEnum::EN, new LocaleProvider($requestStack)->getCurrent());
    }

    public function test_locale_provider_rejects_a_missing_current_request(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Current locale is not set.');

        new LocaleProvider(new RequestStack())->getCurrent();
    }
}
