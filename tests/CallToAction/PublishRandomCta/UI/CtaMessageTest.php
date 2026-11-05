<?php

declare(strict_types=1);

namespace App\Tests\CallToAction\PublishRandomCta\UI;

use App\CallToAction\PublishRandomCta\Application\CtaPublicationChannel;
use App\CallToAction\PublishRandomCta\UI\Twig\CtaMessage;
use App\Shared\Domain\LocaleEnum;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Translation\LocaleSwitcher;
use Twig\Environment;

/**
 * @internal
 *
 * @covers \App\CallToAction\PublishRandomCta\Application\CtaPublicationChannel
 * @covers \App\CallToAction\PublishRandomCta\UI\Twig\CtaMessage
 */
final class CtaMessageTest extends KernelTestCase
{
    public function test_it_exposes_the_shared_publication_target_and_localized_topic(): void
    {
        $component = new CtaMessage();
        $component->locale = LocaleEnum::PL;

        self::assertSame(CtaPublicationChannel::TARGET, $component->target);
        self::assertSame('cta_message_topic_pl', $component->getTopic());
    }

    #[DataProvider('localeProvider')]
    public function test_it_renders_the_existing_localized_template_contract(
        LocaleEnum $locale,
        string $expectedMessage,
        string $expectedButton,
    ): void {
        self::bootKernel();
        $twig = self::getContainer()->get(Environment::class);
        self::assertInstanceOf(Environment::class, $twig);
        $localeSwitcher = self::getContainer()->get(LocaleSwitcher::class);
        self::assertInstanceOf(LocaleSwitcher::class, $localeSwitcher);

        $rendered = $localeSwitcher->runWithLocale(
            $locale->value,
            static fn (): string => $twig
                ->createTemplate('{{ component("CtaMessage", {locale: locale}) }}')
                ->render(['locale' => $locale]),
        );

        self::assertStringContainsString('cta_message_topic_'.$locale->value, $rendered);
        self::assertStringContainsString('<turbo-mercure-stream-source src="', $rendered);
        self::assertStringContainsString('?match=cta_message_topic_'.$locale->value, $rendered);
        self::assertStringNotContainsString('?topic=', $rendered);
        self::assertStringContainsString('id="'.CtaPublicationChannel::TARGET.'"', $rendered);
        self::assertStringContainsString($expectedMessage, $rendered);
        self::assertStringContainsString('<span>'.$expectedButton.'</span>', $rendered);
        self::assertStringContainsString('data-turbo-frame="turbo_frame_page_content"', $rendered);
        self::assertStringContainsString('data-turbo-action="advance"', $rendered);
    }

    /**
     * @return iterable<string, array{LocaleEnum, string, string}>
     */
    public static function localeProvider(): iterable
    {
        yield 'English' => [LocaleEnum::EN, 'Please feel free to contact me.', 'Contact'];

        yield 'Polish' => [LocaleEnum::PL, 'Zapraszam do kontaktu.', 'Kontakt'];
    }
}
