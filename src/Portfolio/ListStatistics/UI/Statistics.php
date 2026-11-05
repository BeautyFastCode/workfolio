<?php

declare(strict_types=1);

namespace App\Portfolio\ListStatistics\UI;

use App\Portfolio\ListStatistics\Application\ListStatisticsHandler;
use App\Portfolio\ListStatistics\Application\ListStatisticsQuery;
use App\Portfolio\ListStatistics\Application\StatisticGroupView;
use App\Shared\Domain\LocaleEnum;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\PostMount;

#[AsTwigComponent(name: 'Statistics', template: 'portfolio/list_statistics/components/Statistics.html.twig')]
final class Statistics
{
    public LocaleEnum $locale = LocaleEnum::EN;

    /**
     * @var list<StatisticGroupView>
     */
    public array $data = [];

    public function __construct(
        private readonly ListStatisticsHandler $handler,
    ) {}

    #[PostMount]
    public function postMount(): void
    {
        $result = ($this->handler)(new ListStatisticsQuery($this->locale->value));

        $this->data = $result->groups;
    }
}
