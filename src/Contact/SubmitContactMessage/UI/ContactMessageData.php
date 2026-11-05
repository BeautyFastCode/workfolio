<?php

declare(strict_types=1);

namespace App\Contact\SubmitContactMessage\UI;

use Symfony\Component\Validator\Constraints as Assert;

final class ContactMessageData
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 150)]
    public string $fullName = '';

    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(max: 180)]
    public string $email = '';

    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 200)]
    public string $subject = '';

    #[Assert\NotBlank]
    #[Assert\Length(min: 10, max: 5000)]
    public string $message = '';
}
