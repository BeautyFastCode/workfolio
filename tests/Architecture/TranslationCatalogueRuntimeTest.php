<?php

declare(strict_types=1);

namespace App\Tests\Architecture;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 *
 * @coversNothing
 */
final class TranslationCatalogueRuntimeTest extends KernelTestCase
{
    #[DataProvider('criticalTranslationProvider')]
    public function test_critical_translations_are_loaded_from_module_directories(
        string $domain,
        string $key,
        string $english,
        string $polish,
    ): void {
        self::bootKernel();

        $translator = self::getContainer()->get(TranslatorInterface::class);
        self::assertInstanceOf(TranslatorInterface::class, $translator);
        self::assertSame($english, $translator->trans($key, domain: $domain, locale: 'en'));
        self::assertSame($polish, $translator->trans($key, domain: $domain, locale: 'pl'));
    }

    /**
     * @return iterable<string, array{string, string, string, string}>
     */
    public static function criticalTranslationProvider(): iterable
    {
        yield 'Shared public page' => ['messages', 'button.contact', 'Contact', 'Kontakt'];

        yield 'Contact form' => ['messages', 'contact_message.submit', 'Send message', 'Wyślij wiadomość'];

        yield 'CallToAction public component' => ['messages', 'cta_message.default', 'Please feel free to contact me.', 'Zapraszam do kontaktu.'];

        yield 'Portfolio public task' => ['messages', 'task.tools', 'Tools', 'Narzędzia'];

        yield 'Shared dashboard' => ['admin', 'menu.dashboard', 'Dashboard', 'Panel administracyjny'];

        yield 'Contact link CRUD' => ['admin', 'crud.contact_link.entity_label.plural', 'Contact links', 'Linki kontaktowe'];

        yield 'Contact message CRUD' => ['admin', 'crud.contact_message.entity_label.plural', 'Contact messages', 'Wiadomości kontaktowe'];

        yield 'CallToAction CRUD' => ['admin', 'crud.cta_message.entity_label.plural', 'CTA messages', 'Wiadomości CTA'];

        yield 'Content page CRUD' => ['admin', 'crud.page.entity_label.plural', 'Pages', 'Strony'];

        yield 'Content page block CRUD' => ['admin', 'crud.page_block.entity_label.plural', 'Blocks', 'Bloki'];

        yield 'Content block collection CRUD' => ['admin', 'crud.block_collection.entity_label.plural', 'Blocks collections', 'Kolekcje bloków'];

        yield 'Content block CRUD' => ['admin', 'crud.block.entity_label.plural', 'Blocks', 'Bloki'];

        yield 'Portfolio statistic group CRUD' => ['admin', 'crud.statistic_group.entity_label.plural', 'Statistics groups', 'Grupy statystyk'];

        yield 'Portfolio statistic item CRUD' => ['admin', 'crud.statistic_item.entity_label.plural', 'Statistics', 'Statystyki'];

        yield 'Portfolio technology group CRUD' => ['admin', 'crud.technology_group.entity_label.plural', 'Technologies groups', 'Grupy technologii'];

        yield 'Portfolio technology item CRUD' => ['admin', 'crud.technology_item.entity_label.plural', 'Technologies', 'Technologie'];

        yield 'Portfolio task CRUD' => ['admin', 'crud.task.entity_label.plural', 'Tasks', 'Zadania'];

        yield 'Identity login' => ['admin', 'login.page_title', 'Login into Dashboard', 'Zaloguj się do panelu administracyjnego'];

        yield 'Identity administrator CRUD' => ['admin', 'crud.admin_user.entity_label.plural', 'Administrators', 'Administratorzy'];

        yield 'Shared validator' => ['validators', 'translatable.translations.min_count', 'Add at least one translation.', 'Dodaj przynajmniej jedno tłumaczenie.'];

        yield 'Content page validator' => ['validators', 'page_translation.slug', 'Slug may contain only lowercase letters, numbers and hyphens.', 'Slug może zawierać tylko małe litery, cyfry i myślniki.'];

        yield 'Content block validator' => ['validators', 'block.name', 'Block name may contain only lowercase letters, numbers and underscores.', 'Nazwa bloku może zawierać tylko małe litery, cyfry i podkreślenia.'];

        yield 'Identity validator' => ['validators', 'admin_user.roles.min_count', 'An administrator must have at least one role.', 'Administrator musi mieć co najmniej jedną rolę.'];
    }

    #[DataProvider('fallbackTranslationProvider')]
    public function test_unsupported_locale_falls_back_to_english(
        string $domain,
        string $key,
        string $english,
    ): void {
        self::bootKernel();

        $translator = self::getContainer()->get(TranslatorInterface::class);
        self::assertInstanceOf(TranslatorInterface::class, $translator);
        self::assertSame($english, $translator->trans($key, domain: $domain, locale: 'de'));
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function fallbackTranslationProvider(): iterable
    {
        yield 'messages' => ['messages', 'contact_message.submit', 'Send message'];

        yield 'admin' => ['admin', 'login.page_title', 'Login into Dashboard'];

        yield 'validators' => ['validators', 'admin_user.roles.min_count', 'An administrator must have at least one role.'];
    }
}
