<?php

declare(strict_types=1);

namespace App\Portfolio\ListTasks\Application;

interface TaskFinder
{
    /**
     * @return list<TaskView>
     */
    public function findAll(string $locale): array;
}
