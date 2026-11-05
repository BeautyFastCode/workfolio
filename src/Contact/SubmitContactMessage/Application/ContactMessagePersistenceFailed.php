<?php

declare(strict_types=1);

namespace App\Contact\SubmitContactMessage\Application;

final class ContactMessagePersistenceFailed extends \RuntimeException
{
    public function __construct(\Throwable $previous)
    {
        parent::__construct('The contact message could not be committed.', previous: $previous);
    }
}
