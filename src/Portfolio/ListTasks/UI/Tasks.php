<?php

declare(strict_types=1);

namespace App\Portfolio\ListTasks\UI;

use App\Portfolio\ListTasks\Application\ListTasksHandler;
use App\Portfolio\ListTasks\Application\ListTasksQuery;
use App\Portfolio\ListTasks\Application\TaskView;
use App\Shared\UI\Twig\LocaleProvider;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\PostMount;

#[AsTwigComponent(name: 'Tasks', template: 'portfolio/list_tasks/components/Tasks.html.twig')]
final class Tasks
{
    /**
     * @var list<TaskView>
     */
    public array $data = [];

    public function __construct(
        private readonly ListTasksHandler $handler,
        private readonly LocaleProvider $localeProvider,
    ) {}

    #[PostMount]
    public function postMount(): void
    {
        $result = ($this->handler)(new ListTasksQuery(
            $this->localeProvider->getCurrent()->value,
        ));

        $this->data = $result->items;
    }
}
