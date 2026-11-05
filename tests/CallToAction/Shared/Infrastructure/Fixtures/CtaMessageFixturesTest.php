<?php

declare(strict_types=1);

namespace App\Tests\CallToAction\Shared\Infrastructure\Fixtures;

use App\CallToAction\Shared\Infrastructure\Fixtures\CtaMessageFixtures;
use App\CallToAction\Shared\Infrastructure\Persistence\Doctrine\Entity\CtaMessage;
use App\CallToAction\Shared\Infrastructure\Persistence\Doctrine\Entity\CtaMessageTranslation;
use Doctrine\Bundle\FixturesBundle\Loader\FixturesProvider;
use Doctrine\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class CtaMessageFixturesTest extends KernelTestCase
{
    public function test_fixture_remains_registered_with_the_same_group_and_data(): void
    {
        self::bootKernel();

        $fixturesProvider = self::getContainer()->get('doctrine.fixtures.loader');
        self::assertInstanceOf(FixturesProvider::class, $fixturesProvider);

        $fixtures = $fixturesProvider->getFixtures(['cta_message']);
        self::assertCount(1, $fixtures);
        $fixture = $fixtures[0];
        self::assertInstanceOf(CtaMessageFixtures::class, $fixture);
        self::assertSame(['cta_message'], CtaMessageFixtures::getGroups());

        $ctaMessages = [];
        $translations = [];
        $manager = $this->createMock(ObjectManager::class);
        $manager
            ->expects($this->exactly(30))
            ->method('persist')
            ->willReturnCallback(static function (object $entity) use (&$ctaMessages, &$translations): void {
                if ($entity instanceof CtaMessage) {
                    $ctaMessages[] = $entity;
                }

                if ($entity instanceof CtaMessageTranslation) {
                    $translations[] = $entity;
                }
            })
        ;
        $manager->expects($this->once())->method('flush');

        $fixture->load($manager);

        self::assertCount(10, $ctaMessages);
        self::assertCount(20, $translations);
        self::assertSame([
            ['Contact me, I\'d love to talk.', 'Skontaktuj się ze mną, chętnie porozmawiam.'],
            ['Have any questions? Write to me.', 'Masz pytania? Napisz do mnie.'],
            ['Collaboration? I\'m open to suggestions.', 'Współpraca? Jestem otwarty na propozycje.'],
            ['Write to me and let\'s create something together.', 'Napisz do mnie i stwórzmy coś razem.'],
            ['Let\'s talk about working together.', 'Porozmawiajmy o współpracy.'],
            ['Want to collaborate? Get in touch.', 'Chcesz współpracować? Odezwij się.'],
            ['I\'m available, contact me.', 'Jestem dostępny, skontaktuj się ze mną.'],
            ['Have an idea? Let me know.', 'Masz pomysł? Daj mi znać.'],
            ['Together we can do more, write to me.', 'Wspólnie możemy zrobić więcej, napisz.'],
            ['Feel free to contact me.', 'Zapraszam do kontaktu.'],
        ], array_map(
            static fn (CtaMessage $ctaMessage): array => [
                $ctaMessage->getMessageEn(),
                $ctaMessage->getMessagePl(),
            ],
            $ctaMessages,
        ));
    }
}
