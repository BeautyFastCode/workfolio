<?php

declare(strict_types=1);

namespace App\Identity\CreateAdminUser\Application;

interface AdminUserTransaction
{
    /**
     * @param \Closure(): non-empty-string $operation
     *
     * @return non-empty-string
     */
    public function execute(\Closure $operation): string;
}
