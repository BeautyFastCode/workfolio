<?php

declare(strict_types=1);

namespace App\Contact\Shared\Infrastructure\Fixtures;

use App\Contact\Shared\Infrastructure\Persistence\Doctrine\Entity\ContactMessage;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

final class ContactMessageFixtures extends Fixture implements FixtureGroupInterface
{
    public function load(ObjectManager $manager): void
    {
        $data = [
            [
                'full_name' => 'Anna Kowalska',
                'email' => 'anna.kowalska@example.com',
                'subject' => 'Pytanie o ofertę',
                'message' => 'Dzień dobry, chciałabym dowiedzieć się więcej o dostępnych usługach i cenniku.',
            ],
            [
                'full_name' => 'Michał Nowak',
                'email' => 'michal.nowak@example.com',
                'subject' => 'Prośba o kontakt',
                'message' => 'Proszę o kontakt w sprawie szczegółów współpracy. Jestem dostępny w godzinach popołudniowych.',
            ],
            [
                'full_name' => 'Katarzyna Wiśniewska',
                'email' => 'katarzyna.wisniewska@example.com',
                'subject' => 'Problem z formularzem',
                'message' => 'Podczas wysyłania formularza pojawia się błąd. Proszę o sprawdzenie zgłoszenia.',
            ],
            [
                'full_name' => 'Piotr Zieliński',
                'email' => 'piotr.zielinski@example.com',
                'subject' => 'Zapytanie techniczne',
                'message' => 'Mam pytanie dotyczące konfiguracji usługi oraz możliwości integracji z zewnętrznym systemem.',
            ],
            [
                'full_name' => 'Magdalena Wójcik',
                'email' => 'magdalena.wojcik@example.com',
                'subject' => 'Współpraca',
                'message' => 'Jestem zainteresowana nawiązaniem współpracy i chciałabym poznać dostępne opcje.',
            ],
            [
                'full_name' => 'John Miller',
                'email' => 'john.miller@example.com',
                'subject' => 'Question about pricing',
                'message' => 'Hello, I would like to learn more about your pricing plans and available services.',
            ],
            [
                'full_name' => 'Emily Johnson',
                'email' => 'emily.johnson@example.com',
                'subject' => 'Request for contact',
                'message' => 'Please contact me regarding the details of possible cooperation. I am available in the afternoon.',
            ],
            [
                'full_name' => 'Robert Smith',
                'email' => 'robert.smith@example.com',
                'subject' => 'Technical issue',
                'message' => 'I encountered an error while submitting the form. Could you please check this issue?',
            ],
            [
                'full_name' => 'Laura Brown',
                'email' => 'laura.brown@example.com',
                'subject' => 'Product availability',
                'message' => 'Is the product listed on your website currently available? I would also like to know the estimated delivery time.',
            ],
            [
                'full_name' => 'Daniel Wilson',
                'email' => 'daniel.wilson@example.com',
                'subject' => 'General inquiry',
                'message' => 'I have an additional question that I could not find in the FAQ section. Please reply by email.',
            ],
        ];

        foreach ($data as $item) {
            $contactMessage = new ContactMessage();

            $contactMessage->setFullName($item['full_name']);
            $contactMessage->setEmail($item['email']);
            $contactMessage->setSubject($item['subject']);
            $contactMessage->setMessage($item['message']);

            $manager->persist($contactMessage);
        }

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['contact_message'];
    }
}
