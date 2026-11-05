<?php

declare(strict_types=1);

namespace App\Contact\SubmitContactMessage\Application;

interface ContactMessageNotifier
{
    public function notify(
        SubmitContactMessageCommand $command,
        SubmitContactMessageResult $result,
    ): void;
}
