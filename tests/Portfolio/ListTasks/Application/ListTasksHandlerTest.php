<?php

declare(strict_types=1);

namespace App\Tests\Portfolio\ListTasks\Application;

use App\Portfolio\ListTasks\Application\ListTasksHandler;
use App\Portfolio\ListTasks\Application\ListTasksQuery;
use App\Portfolio\ListTasks\Application\ListTasksResult;
use App\Portfolio\ListTasks\Application\TaskFinder;
use App\Portfolio\ListTasks\Application\TaskView;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \App\Portfolio\ListTasks\Application\ListTasksHandler
 * @covers \App\Portfolio\ListTasks\Application\ListTasksQuery
 * @covers \App\Portfolio\ListTasks\Application\ListTasksResult
 * @covers \App\Portfolio\ListTasks\Application\TaskView
 */
final class ListTasksHandlerTest extends TestCase
{
    public function test_it_returns_tasks_for_the_requested_locale(): void
    {
        $items = [new TaskView(
            'task.webp',
            'Task',
            'Description',
            'PHP',
            'Impact',
        )];
        $finder = $this->createMock(TaskFinder::class);
        $finder
            ->expects(self::once())
            ->method('findAll')
            ->with('pl')
            ->willReturn($items)
        ;

        $result = (new ListTasksHandler($finder))(new ListTasksQuery('pl'));

        self::assertEquals(new ListTasksResult($items), $result);
    }

    public function test_it_returns_an_empty_list(): void
    {
        $finder = $this->createStub(TaskFinder::class);
        $finder->method('findAll')->willReturn([]);

        $result = (new ListTasksHandler($finder))(new ListTasksQuery('en'));

        self::assertSame([], $result->items);
    }
}
