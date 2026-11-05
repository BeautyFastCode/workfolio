<?php

declare(strict_types=1);

namespace App\Contact\SubmitContactMessage\Application;

final readonly class SubmitContactMessageCommand
{
    public function __construct(
        public string $fullName,
        public string $email,
        public string $subject,
        public string $message,
    ) {}
}
