<?php

declare(strict_types=1);

namespace App\Contact\SubmitContactMessage\Application;

final class ContactMessageNotificationFailed extends \RuntimeException
{
    public function __construct(
        public readonly SubmitContactMessageCommand $command,
        public readonly SubmitContactMessageResult $result,
        \Throwable $previous,
    ) {
        parent::__construct(
            'The contact message was committed, but its notification could not be requested.',
            previous: $previous,
        );
    }
}
