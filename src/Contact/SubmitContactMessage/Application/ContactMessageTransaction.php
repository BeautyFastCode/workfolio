<?php

declare(strict_types=1);

namespace App\Contact\SubmitContactMessage\Application;

interface ContactMessageTransaction
{
    /**
     * @param \Closure(): SubmitContactMessageResult $operation
     */
    public function execute(\Closure $operation): SubmitContactMessageResult;
}
