<?php

declare(strict_types=1);

namespace App\Identity\CreateAdminUser\Application;

final class InvalidAdminUser extends \InvalidArgumentException
{
    /**
     * @param non-empty-array<string, non-empty-list<string>> $violations
     */
    public function __construct(
        public readonly array $violations,
    ) {
        parent::__construct('The administrator data is invalid.');
    }
}
