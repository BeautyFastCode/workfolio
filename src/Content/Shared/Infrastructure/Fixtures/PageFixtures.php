<?php

declare(strict_types=1);

namespace App\Content\Shared\Infrastructure\Fixtures;

use App\Content\Shared\Infrastructure\Persistence\Doctrine\Entity\Page;
use App\Content\Shared\Infrastructure\Persistence\Doctrine\Entity\PageBlock;
use App\Content\Shared\Infrastructure\Persistence\Doctrine\Entity\PageBlockTranslation;
use App\Content\Shared\Infrastructure\Persistence\Doctrine\Entity\PageTranslation;
use App\Content\Shared\Infrastructure\Persistence\Doctrine\Enum\BlockType;
use App\Shared\Domain\LocaleEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

final class PageFixtures extends Fixture implements FixtureGroupInterface
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
                'titleEn' => 'Technologies',
                'titlePl' => 'Technologie',
                'slugEn' => 'technologies',
                'slugPl' => 'technologie',
                'menuTitleEn' => 'Technologies',
                'menuTitlePl' => 'Technologie',
                'template' => 'technologies.html.twig',
                'blocks' => [
                    ['name' => 'block_1', 'type' => 'html', 'contentEn' => '<p class="mb-0">'.$paragrafEn.'</p>', 'contentPl' => '<p class="mb-0">'.$paragrafPl.'</p>'],
                    ['name' => 'title_1', 'type' => 'text', 'contentEn' => 'Key technologies', 'contentPl' => 'Kluczowe technologie'],
                    ['name' => 'title_2', 'type' => 'text', 'contentEn' => 'Other technologies I have worked with.', 'contentPl' => 'Pozostałe technologie, z którymi pracowałem.'],
                ],
            ],
            [
                'titleEn' => 'Tasks',
                'titlePl' => 'Zadania',
                'slugEn' => 'tasks',
                'slugPl' => 'zadania',
                'menuTitleEn' => 'Tasks',
                'menuTitlePl' => 'Zadania',
                'template' => 'default.html.twig',
                'blocks' => [
                    ['name' => 'block_1', 'type' => 'html', 'contentEn' => '<p class="mb-0">'.$paragrafEn.'</p>', 'contentPl' => '<p class="mb-0">'.$paragrafPl.'</p>'],
                    ['name' => 'block_2', 'type' => 'twig', 'contentEn' => '[[Tasks]]', 'contentPl' => '[[Tasks]]'],
                ],
            ],
            [
                'titleEn' => 'About me',
                'titlePl' => 'O mnie',
                'slugEn' => 'about',
                'slugPl' => 'o-mnie',
                'menuTitleEn' => 'About',
                'menuTitlePl' => 'O mnie',
                'template' => 'default.html.twig',
                'blocks' => [
                    ['name' => 'block_1', 'type' => 'html', 'contentEn' => '<p class="mb-3">'.$paragrafEn.'</p>', 'contentPl' => '<p class="mb-3">'.$paragrafPl.'</p>'],
                    ['name' => 'block_2', 'type' => 'html', 'contentEn' => '<p class="mb-0">'.$paragrafEn.'</p>', 'contentPl' => '<p class="mb-0">'.$paragrafPl.'</p>'],
                ],
            ],
            [
                'titleEn' => 'Feel free to contact me.',
                'titlePl' => 'Możesz się ze mną skontaktować.',
                'menuTitleEn' => 'Contact',
                'menuTitlePl' => 'Kontakt',
                'slugEn' => 'contact',
                'slugPl' => 'kontakt',
                'template' => 'default.html.twig',
                'blocks' => [
                    ['name' => 'block_1', 'type' => 'html', 'contentEn' => '<p class="mb-0">'.$paragrafEn.'</p>', 'contentPl' => '<p class="mb-0">'.$paragrafPl.'</p>'],
                    ['name' => 'block_2', 'type' => 'twig', 'contentEn' => '[[ContactData]]', 'contentPl' => '[[ContactData]]'],
                    ['name' => 'block_3', 'type' => 'twig', 'contentEn' => '[[ContactForm]]', 'contentPl' => '[[ContactForm]]'],
                ],
            ],
            [
                'titleEn' => 'Portfolio of projects',
                'titlePl' => 'Portfolio projektów',
                'menuTitleEn' => 'Projects',
                'menuTitlePl' => 'Projekty',
                'slugEn' => 'projects',
                'slugPl' => 'projekty',
                'template' => 'default.html.twig',
                'blocks' => [
                    ['name' => 'block_1', 'type' => 'html', 'contentEn' => '<p class="mb-3">'.$paragrafEn.'</p>', 'contentPl' => '<p class="mb-3">'.$paragrafPl.'</p>'],
                    ['name' => 'block_2', 'type' => 'html', 'contentEn' => '<p class="mb-3">'.$paragrafEn.'</p>', 'contentPl' => '<p class="mb-3">'.$paragrafPl.'</p>'],
                ],
            ],
        ];

        foreach ($data as $pagePosition => $pageData) {
            $page = new Page();
            $page->setPosition($pagePosition + 1);
            $page->setPublished(true);
            $page->setContentTemplate($pageData['template']);

            $translationEn = new PageTranslation();
            $translationEn->setLocale(LocaleEnum::EN);
            $translationEn->setTitle($pageData['titleEn']);
            $translationEn->setSlug($pageData['slugEn']);
            $translationEn->setMenuTitle($pageData['menuTitleEn']);

            $translationPl = new PageTranslation();
            $translationPl->setLocale(LocaleEnum::PL);
            $translationPl->setTitle($pageData['titlePl']);
            $translationPl->setSlug($pageData['slugPl']);
            $translationPl->setMenuTitle($pageData['menuTitlePl']);

            $page->addTranslation($translationEn);
            $page->addTranslation($translationPl);

            foreach ($pageData['blocks'] as $blockPosition => $blockData) {
                $pageBlock = new PageBlock();
                $pageBlock->setPosition($blockPosition + 1);
                $pageBlock->setName($blockData['name']);
                $pageBlock->setType(BlockType::from($blockData['type']));

                $pageBlockTranslationEn = new PageBlockTranslation();
                $pageBlockTranslationEn->setLocale(LocaleEnum::EN);
                $pageBlockTranslationEn->setContent($blockData['contentEn']);

                $pageBlockTranslationPl = new PageBlockTranslation();
                $pageBlockTranslationPl->setLocale(LocaleEnum::PL);
                $pageBlockTranslationPl->setContent($blockData['contentPl']);

                $pageBlock->addTranslation($pageBlockTranslationEn);
                $pageBlock->addTranslation($pageBlockTranslationPl);

                $manager->persist($pageBlockTranslationEn);
                $manager->persist($pageBlockTranslationPl);
                $manager->persist($pageBlock);

                $page->addBlock($pageBlock);
            }

            $manager->persist($translationEn);
            $manager->persist($translationPl);
            $manager->persist($page);
        }

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['page'];
    }
}
