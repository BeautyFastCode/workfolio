<?php

declare(strict_types=1);

namespace App\CallToAction\PublishRandomCta\UI\Scheduler;

use Symfony\Component\Uid\Uuid;

final readonly class PublishCtaMessage
{
    private Uuid $id;

    public function __construct()
    {
        $this->id = Uuid::v7();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }
}
