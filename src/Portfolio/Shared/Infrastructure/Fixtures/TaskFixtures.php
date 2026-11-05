<?php

declare(strict_types=1);

namespace App\Portfolio\Shared\Infrastructure\Fixtures;

use App\Portfolio\Shared\Infrastructure\Persistence\Doctrine\Entity\Task;
use App\Portfolio\Shared\Infrastructure\Persistence\Doctrine\Entity\TaskTranslation;
use App\Shared\Domain\LocaleEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

final class TaskFixtures extends Fixture implements FixtureGroupInterface
{
    public function load(ObjectManager $manager): void
    {
        $data = [
            [
                'nameEn' => 'HR Analytics Report for Workforce Planning',
                'namePl' => 'Raport analityczny HR do planowania zatrudnienia',
                'descriptionEn' => 'Built an interactive workforce planning dashboard to help HR teams monitor headcount, overtime, turnover risk and department-level staffing needs.',
                'descriptionPl' => 'Przygotowanie interaktywnego dashboardu wspierającego zespół HR w monitorowaniu liczby pracowników, nadgodzin, ryzyka rotacji oraz potrzeb kadrowych w poszczególnych działach.',
                'toolsEn' => 'Excel, Power BI, SQL',
                'toolsPl' => 'Excel, Power BI, SQL',
                'impactEn' => 'Reduced overtime cost by 19% and improved staffing allocation efficiency.',
                'impactPl' => 'Obniżenie kosztów nadgodzin o 19% oraz poprawa efektywności alokacji pracowników.',
            ],
            [
                'nameEn' => 'Employee Engagement Survey Analysis',
                'namePl' => 'Analiza ankiety zaangażowania pracowników',
                'descriptionEn' => 'Analyzed employee survey results, identified key engagement drivers and prepared recommendations for improving team satisfaction and retention.',
                'descriptionPl' => 'Analiza wyników ankiety pracowniczej, identyfikacja kluczowych czynników wpływających na zaangażowanie oraz przygotowanie rekomendacji poprawiających satysfakcję i retencję zespołu.',
                'toolsEn' => 'Google Forms, Excel, Tableau',
                'toolsPl' => 'Google Forms, Excel, Tableau',
                'impactEn' => 'Helped increase employee engagement score by 14% over the next review cycle.',
                'impactPl' => 'Wzrost wskaźnika zaangażowania pracowników o 14% w kolejnym cyklu badania.',
            ],
            [
                'nameEn' => 'Recruitment Pipeline Optimization',
                'namePl' => 'Optymalizacja procesu rekrutacyjnego',
                'descriptionEn' => 'Created a reporting process for tracking candidates across recruitment stages, measuring time-to-hire and identifying bottlenecks in the hiring funnel.',
                'descriptionPl' => 'Stworzenie procesu raportowania kandydatów na kolejnych etapach rekrutacji, pomiar czasu zatrudnienia oraz identyfikacja wąskich gardeł w lejku rekrutacyjnym.',
                'toolsEn' => 'Airtable, SQL, Looker Studio',
                'toolsPl' => 'Airtable, SQL, Looker Studio',
                'impactEn' => 'Shortened average time-to-hire by 22% and improved visibility for hiring managers.',
                'impactPl' => 'Skrócenie średniego czasu zatrudnienia o 22% oraz poprawa przejrzystości procesu dla hiring managerów.',
            ],
        ];

        foreach ($data as $positon => $datum) {
            $task = new Task();
            $task->setPosition($positon + 1);

            $translationEn = new TaskTranslation();
            $translationEn->setLocale(LocaleEnum::EN);
            $translationEn->setName($datum['nameEn']);
            $translationEn->setDescription($datum['descriptionEn']);
            $translationEn->setTools($datum['toolsEn']);
            $translationEn->setImpact($datum['impactEn']);

            $translationPl = new TaskTranslation();
            $translationPl->setLocale(LocaleEnum::PL);
            $translationPl->setName($datum['namePl']);
            $translationPl->setDescription($datum['descriptionPl']);
            $translationPl->setTools($datum['toolsPl']);
            $translationPl->setImpact($datum['impactPl']);

            $task->addTranslation($translationEn);
            $task->addTranslation($translationPl);

            $manager->persist($translationEn);
            $manager->persist($translationPl);
            $manager->persist($task);
        }

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['task'];
    }
}
