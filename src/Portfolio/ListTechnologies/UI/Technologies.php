<?php

declare(strict_types=1);

namespace App\Portfolio\ListTechnologies\UI;

use App\Portfolio\ListTechnologies\Application\ListTechnologiesHandler;
use App\Portfolio\ListTechnologies\Application\ListTechnologiesQuery;
use App\Portfolio\ListTechnologies\Application\TechnologyGroupView;
use App\Shared\Domain\LocaleEnum;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\PostMount;

#[AsTwigComponent(name: 'Technologies', template: 'portfolio/list_technologies/components/Technologies.html.twig')]
final class Technologies
{
    public LocaleEnum $locale = LocaleEnum::EN;

    public bool $highlighted = true;

    /**
     * @var list<TechnologyGroupView>
     */
    public array $data = [];

    public function __construct(
        private readonly ListTechnologiesHandler $handler,
    ) {}

    #[PostMount]
    public function postMount(): void
    {
        $result = ($this->handler)(new ListTechnologiesQuery(
            $this->locale->value,
            $this->highlighted,
        ));

        $this->data = $result->groups;
    }
}
