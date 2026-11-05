<?php

declare(strict_types=1);

namespace App\Content\Shared\Infrastructure\Fixtures;

use App\Content\Shared\Infrastructure\Persistence\Doctrine\Entity\Block;
use App\Content\Shared\Infrastructure\Persistence\Doctrine\Entity\BlockCollection;
use App\Content\Shared\Infrastructure\Persistence\Doctrine\Entity\BlockTranslation;
use App\Content\Shared\Infrastructure\Persistence\Doctrine\Enum\BlockType;
use App\Shared\Domain\LocaleEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class BlockFixtures extends Fixture implements FixtureGroupInterface
{
    public function load(ObjectManager $manager): void
    {
        $paragrafEn = '
                This is a sample paragraph created for testing purposes.
                It helps verify how longer text blocks are displayed in different parts of the application.
                The content is neutral, readable, and suitable for fixtures, mock data, and interface previews.
            ';

        $paragrafPl = '
                To jest przykładowy akapit przygotowany na potrzeby testów.
                Pomaga sprawdzić, jak dłuższe bloki tekstu wyświetlają się w różnych częściach aplikacji.
                Treść jest neutralna, czytelna i odpowiednia do fixture’ów, danych testowych oraz podglądów interfejsu.
            ';

        $data = [
            [
                'name' => 'header',
                'blocks' => [
                    ['name' => 'site_name', 'type' => 'text', 'contentEn' => 'workfolio', 'contentPl' => 'workfolio'],
                    ['name' => 'title', 'type' => 'text', 'contentEn' => 'Name and Surname', 'contentPl' => 'Imię i Nazwisko'],
                    ['name' => 'subtitle', 'type' => 'text', 'contentEn' => 'professional title', 'contentPl' => 'tytuł zawodowy'],
                    ['name' => 'about', 'type' => 'text', 'contentEn' => $paragrafEn, 'contentPl' => $paragrafPl],
                ],
            ],
            [
                'name' => 'footer',
                'blocks' => [
                    ['name' => 'copyright', 'type' => 'text', 'contentEn' => 'Copyright © 2026 Name and Surname', 'contentPl' => 'Prawa autorskie © 2026 Imię i nazwisko'],
                ],
            ],
        ];

        foreach ($data as $collectingData) {
            $blockCollection = new BlockCollection();

            $blockCollection->setName($collectingData['name']);

            foreach ($collectingData['blocks'] as $blockPosition => $blockData) {
                $block = new Block();
                $block->setPosition($blockPosition + 1);
                $block->setName($blockData['name']);
                $block->setType(BlockType::from($blockData['type']));

                $blockTranslationEn = new BlockTranslation();
                $blockTranslationEn->setLocale(LocaleEnum::EN);
                $blockTranslationEn->setContent($blockData['contentEn']);

                $blockTranslationPl = new BlockTranslation();
                $blockTranslationPl->setLocale(LocaleEnum::PL);
                $blockTranslationPl->setContent($blockData['contentPl']);

                $block->addTranslation($blockTranslationEn);
                $block->addTranslation($blockTranslationPl);

                $manager->persist($blockTranslationEn);
                $manager->persist($blockTranslationPl);
                $manager->persist($block);

                $blockCollection->addBlock($block);
            }

            $manager->persist($blockCollection);
        }

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['block'];
    }
}
