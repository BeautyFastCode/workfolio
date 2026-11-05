<?php

declare(strict_types=1);

namespace App\Contact\Shared\Infrastructure\Fixtures;

use App\Contact\Shared\Infrastructure\Persistence\Doctrine\Entity\ContactLink;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

final class ContactLinkFixtures extends Fixture implements FixtureGroupInterface
{
    public function load(ObjectManager $manager): void
    {
        $contacts = [
            ['label' => 'E-mail', 'icon' => 'fa-solid fa-envelope', 'href' => 'mailto:office@gmail.com'],
            ['label' => 'Github', 'icon' => 'fa-brands fa-github', 'href' => 'https://github.com/SuperRepo'],
            ['label' => 'LinkedIn', 'icon' => 'fa-brands fa-linkedin', 'href' => 'https://www.linkedin.com/in/name-surname/'],
        ];

        foreach ($contacts as $position => $contact) {
            $contactLink = new ContactLink();

            $contactLink->setPosition($position);
            $contactLink->setName($contact['label']);
            $contactLink->setIcon($contact['icon']);
            $contactLink->setHref($contact['href']);
            $contactLink->setEnabled(true);

            $manager->persist($contactLink);
        }

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['contact_link'];
    }
}
