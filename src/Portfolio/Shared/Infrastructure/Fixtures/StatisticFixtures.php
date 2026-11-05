<?php

declare(strict_types=1);

namespace App\Portfolio\Shared\Infrastructure\Fixtures;

use App\Portfolio\Shared\Infrastructure\Persistence\Doctrine\Entity\StatisticGroup;
use App\Portfolio\Shared\Infrastructure\Persistence\Doctrine\Entity\StatisticGroupTranslation;
use App\Portfolio\Shared\Infrastructure\Persistence\Doctrine\Entity\StatisticItem;
use App\Portfolio\Shared\Infrastructure\Persistence\Doctrine\Entity\StatisticItemTranslation;
use App\Shared\Domain\LocaleEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

final class StatisticFixtures extends Fixture implements FixtureGroupInterface
{
    public function load(ObjectManager $manager): void
    {
        $data = [
            [
                'labelEn' => 'Company achievements',
                'labelPl' => 'Osiągnięcia firmy',
                'items' => [
                    ['icon' => 'fa-regular fa-face-smile', 'value' => '21+', 'labelEn' => 'Client satisfaction rate', 'labelPl' => 'Wskaźnik zadowolenia klienta'],
                    ['icon' => 'fa-solid fa-diagram-project', 'value' => '300', 'labelEn' => 'Created projects', 'labelPl' => 'Utworzone projekty'],
                    ['icon' => 'fa-solid fa-chart-line', 'value' => '94%', 'labelEn' => 'Better performance', 'labelPl' => 'Lepsza wydajność'],
                    ['icon' => 'fa-solid fa-mug-hot', 'value' => '125', 'labelEn' => 'Drunk coffee', 'labelPl' => 'Wypite kawy'],
                ],
            ],
            [
                'labelEn' => 'Team statistics',
                'labelPl' => 'Statystyki zespołu',
                'items' => [
                    ['icon' => 'fa-solid fa-users', 'value' => '18', 'labelEn' => 'Team members', 'labelPl' => 'Członków zespołu'],
                    ['icon' => 'fa-solid fa-code', 'value' => '1M+', 'labelEn' => 'Lines of code', 'labelPl' => 'Linii kodu'],
                    ['icon' => 'fa-solid fa-clock', 'value' => '8+', 'labelEn' => 'Years of experience', 'labelPl' => 'Lat doświadczenia'],
                    ['icon' => 'fa-solid fa-globe', 'value' => '12', 'labelEn' => 'Countries served', 'labelPl' => 'Krajów'],
                ],
            ],
            [
                'labelEn' => 'Business growth',
                'labelPl' => 'Wzrost biznesu',
                'items' => [
                    ['icon' => 'fa-solid fa-arrow-trend-up', 'value' => '150%', 'labelEn' => 'Revenue growth', 'labelPl' => 'Wzrost przychodów'],
                    ['icon' => 'fa-solid fa-handshake', 'value' => '85+', 'labelEn' => 'Business partners', 'labelPl' => 'Partnerów biznesowych'],
                    ['icon' => 'fa-solid fa-award', 'value' => '14', 'labelEn' => 'Industry awards', 'labelPl' => 'Nagrody branżowe'],
                    ['icon' => 'fa-solid fa-bolt', 'value' => '99.9%', 'labelEn' => 'System uptime', 'labelPl' => 'Czas sprawności systemu'],
                ],
            ],
        ];

        foreach ($data as $groupPosition => $groupData) {
            $group = new StatisticGroup();
            $group->setPosition($groupPosition + 1);

            $translationEn = new StatisticGroupTranslation();
            $translationEn->setLocale(LocaleEnum::EN);
            $translationEn->setName($groupData['labelEn']);

            $translationPl = new StatisticGroupTranslation();
            $translationPl->setLocale(LocaleEnum::PL);
            $translationPl->setName($groupData['labelPl']);

            $group->addTranslation($translationEn);
            $group->addTranslation($translationPl);

            $manager->persist($translationEn);
            $manager->persist($translationPl);
            $manager->persist($group);

            foreach ($groupData['items'] as $itemPosition => $itemData) {
                $item = new StatisticItem();
                $item->setIcon($itemData['icon']);
                $item->setValue($itemData['value']);
                $item->setPosition($itemPosition + 1);

                $translationItemEn = new StatisticItemTranslation();
                $translationItemEn->setLocale(LocaleEnum::EN);
                $translationItemEn->setName($itemData['labelEn']);

                $translationItemPl = new StatisticItemTranslation();
                $translationItemPl->setLocale(LocaleEnum::PL);
                $translationItemPl->setName($itemData['labelPl']);

                $item->addTranslation($translationItemEn);
                $item->addTranslation($translationItemPl);

                $group->addItem($item);

                $manager->persist($translationItemEn);
                $manager->persist($translationItemPl);
                $manager->persist($item);
            }
        }

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['statistic'];
    }
}
