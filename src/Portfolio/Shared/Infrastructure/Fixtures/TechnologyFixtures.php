<?php

declare(strict_types=1);

namespace App\Portfolio\Shared\Infrastructure\Fixtures;

use App\Portfolio\Shared\Infrastructure\Persistence\Doctrine\Entity\TechnologyGroup;
use App\Portfolio\Shared\Infrastructure\Persistence\Doctrine\Entity\TechnologyGroupTranslation;
use App\Portfolio\Shared\Infrastructure\Persistence\Doctrine\Entity\TechnologyItem;
use App\Shared\Domain\LocaleEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

final class TechnologyFixtures extends Fixture implements FixtureGroupInterface
{
    public function load(ObjectManager $manager): void
    {
        $data = [
            [
                'labelEn' => 'PHP Symfony',
                'labelPl' => 'PHP Symfony',
                'highlighted' => true,
                'items' => [
                    ['icon' => 'fa-regular fa-face-smile', 'name' => 'React'],
                    ['icon' => 'fa-solid fa-diagram-project', 'name' => 'Node.js'],
                    ['icon' => 'fa-solid fa-chart-line', 'name' => 'TypeScript'],
                    ['icon' => 'fa-solid fa-mug-hot', 'name' => 'Express.js'],
                ],
            ],
            [
                'labelEn' => 'Backend / Databases',
                'labelPl' => 'Backend / Baza danych',
                'highlighted' => true,
                'items' => [
                    ['icon' => 'fa-solid fa-users', 'name' => 'Node.js'],
                    ['icon' => 'fa-solid fa-code', 'name' => 'MongoDB'],
                    ['icon' => 'fa-solid fa-clock', 'name' => 'PostgreSQL'],
                ],
            ],
            [
                'labelEn' => 'Tools',
                'labelPl' => 'Narzędzia',
                'highlighted' => true,
                'items' => [
                    ['icon' => 'fa-solid fa-arrow-trend-up', 'name' => 'Git'],
                    ['icon' => 'fa-solid fa-handshake', 'name' => 'GitHub'],
                    ['icon' => 'fa-solid fa-award', 'name' => 'Docker'],
                    ['icon' => 'fa-solid fa-bolt', 'name' => 'VS Code'],
                ],
            ],
            [
                'labelEn' => 'Backend / Databases',
                'labelPl' => 'Backend / Baza danych',
                'highlighted' => false,
                'items' => [
                    ['icon' => 'fa-solid fa-users', 'name' => 'Node.js'],
                    ['icon' => 'fa-solid fa-code', 'name' => 'MongoDB'],
                    ['icon' => 'fa-solid fa-clock', 'name' => 'PostgreSQL'],
                ],
            ],
            [
                'labelEn' => 'Tools',
                'labelPl' => 'Narzędzia',
                'highlighted' => false,
                'items' => [
                    ['icon' => 'fa-solid fa-arrow-trend-up', 'name' => 'Git'],
                    ['icon' => 'fa-solid fa-handshake', 'name' => 'GitHub'],
                    ['icon' => 'fa-solid fa-award', 'name' => 'Docker'],
                    ['icon' => 'fa-solid fa-bolt', 'name' => 'VS Code'],
                    ['icon' => 'fa-solid fa-users', 'name' => 'Node.js'],
                    ['icon' => 'fa-solid fa-code', 'name' => 'MongoDB'],
                    ['icon' => 'fa-solid fa-clock', 'name' => 'PostgreSQL'],
                ],
            ],
        ];

        foreach ($data as $groupPosition => $groupData) {
            $group = new TechnologyGroup();
            $group->setPosition($groupPosition + 1);
            $group->setHighlighted($groupData['highlighted']);

            $translationEn = new TechnologyGroupTranslation();
            $translationEn->setLocale(LocaleEnum::EN);
            $translationEn->setName($groupData['labelEn']);

            $translationPl = new TechnologyGroupTranslation();
            $translationPl->setLocale(LocaleEnum::PL);
            $translationPl->setName($groupData['labelPl']);

            $group->addTranslation($translationEn);
            $group->addTranslation($translationPl);

            $manager->persist($translationEn);
            $manager->persist($translationPl);
            $manager->persist($group);

            foreach ($groupData['items'] as $itemPosition => $itemData) {
                $item = new TechnologyItem();
                $item->setPosition($itemPosition + 1);
                $item->setIcon($itemData['icon']);
                $item->setName($itemData['name']);

                $group->addItem($item);

                $manager->persist($item);
            }
        }

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['technology'];
    }
}
