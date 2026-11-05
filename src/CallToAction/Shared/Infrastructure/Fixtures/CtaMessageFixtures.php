<?php

declare(strict_types=1);

namespace App\CallToAction\Shared\Infrastructure\Fixtures;

use App\CallToAction\Shared\Infrastructure\Persistence\Doctrine\Entity\CtaMessage;
use App\CallToAction\Shared\Infrastructure\Persistence\Doctrine\Entity\CtaMessageTranslation;
use App\Shared\Domain\LocaleEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

final class CtaMessageFixtures extends Fixture implements FixtureGroupInterface
{
    public function load(ObjectManager $manager): void
    {
        $data = [
            ['messageEn' => 'Contact me, I\'d love to talk.', 'messagePl' => 'Skontaktuj się ze mną, chętnie porozmawiam.'],
            ['messageEn' => 'Have any questions? Write to me.', 'messagePl' => 'Masz pytania? Napisz do mnie.'],
            ['messageEn' => 'Collaboration? I\'m open to suggestions.', 'messagePl' => 'Współpraca? Jestem otwarty na propozycje.'],
            ['messageEn' => 'Write to me and let\'s create something together.', 'messagePl' => 'Napisz do mnie i stwórzmy coś razem.'],
            ['messageEn' => 'Let\'s talk about working together.', 'messagePl' => 'Porozmawiajmy o współpracy.'],
            ['messageEn' => 'Want to collaborate? Get in touch.', 'messagePl' => 'Chcesz współpracować? Odezwij się.'],
            ['messageEn' => 'I\'m available, contact me.', 'messagePl' => 'Jestem dostępny, skontaktuj się ze mną.'],
            ['messageEn' => 'Have an idea? Let me know.', 'messagePl' => 'Masz pomysł? Daj mi znać.'],
            ['messageEn' => 'Together we can do more, write to me.', 'messagePl' => 'Wspólnie możemy zrobić więcej, napisz.'],
            ['messageEn' => 'Feel free to contact me.', 'messagePl' => 'Zapraszam do kontaktu.'],
        ];

        foreach ($data as $item) {
            $cta = new CtaMessage();

            $translationEn = new CtaMessageTranslation();
            $translationEn->setLocale(LocaleEnum::EN);
            $translationEn->setMessage($item['messageEn']);

            $translationPl = new CtaMessageTranslation();
            $translationPl->setLocale(LocaleEnum::PL);
            $translationPl->setMessage($item['messagePl']);

            $cta->addTranslation($translationEn);
            $cta->addTranslation($translationPl);

            $manager->persist($translationEn);
            $manager->persist($translationPl);
            $manager->persist($cta);
        }

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['cta_message'];
    }
}
