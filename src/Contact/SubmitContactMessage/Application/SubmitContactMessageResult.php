<?php

declare(strict_types=1);

namespace App\Contact\SubmitContactMessage\Application;

final readonly class SubmitContactMessageResult
{
    public function __construct(
        public string $contactMessageId,
        public \DateTimeImmutable $submittedAt,
    ) {}
}
