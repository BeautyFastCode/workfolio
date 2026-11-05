<?php

declare(strict_types=1);

namespace App\Contact\Shared\Infrastructure\Persistence\Doctrine\Entity;

use App\Shared\Infrastructure\Persistence\Doctrine\Behavior\TimestampableInterface;
use App\Shared\Infrastructure\Persistence\Doctrine\Behavior\TimestampableTrait;
use App\Shared\Infrastructure\Persistence\Doctrine\Entity\BaseEntity;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'app_contact_message')]
class ContactMessage extends BaseEntity implements TimestampableInterface
{
    use TimestampableTrait;

    #[ORM\Column(name: 'full_name', type: Types::STRING, length: 150)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 150)]
    private string $fullName;

    #[ORM\Column(name: 'email', type: Types::STRING, length: 180)]
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(max: 180)]
    private string $email;

    #[ORM\Column(name: 'subject', type: Types::STRING, length: 200)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 200)]
    private string $subject;

    #[ORM\Column(name: 'message', type: Types::TEXT)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 10, max: 5000)]
    private string $message;

    public function __construct()
    {
        parent::__construct();

        $this->initializeCreatedAt();
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function setFullName(string $fullName): void
    {
        $this->fullName = $fullName;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): void
    {
        $this->subject = $subject;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }
}
