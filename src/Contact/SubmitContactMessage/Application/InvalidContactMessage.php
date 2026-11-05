<?php

declare(strict_types=1);

namespace App\Contact\SubmitContactMessage\Application;

final class InvalidContactMessage extends \RuntimeException
{
    /**
     * @param array<string, list<string>> $violations
     */
    public function __construct(public readonly array $violations)
    {
        parent::__construct('The contact message is invalid.');
    }
}
