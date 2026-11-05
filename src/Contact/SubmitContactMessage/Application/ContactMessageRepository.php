<?php

declare(strict_types=1);

namespace App\Contact\SubmitContactMessage\Application;

interface ContactMessageRepository
{
    public function save(SubmitContactMessageCommand $command): SubmitContactMessageResult;
}
