<?php

declare(strict_types=1);

namespace App\Contact\SubmitContactMessage\Infrastructure;

use App\Contact\SubmitContactMessage\Application\ContactMessageNotifier;
use App\Contact\SubmitContactMessage\Application\SubmitContactMessageCommand;
use App\Contact\SubmitContactMessage\Application\SubmitContactMessageResult;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

final readonly class MailerContactMessageNotifier implements ContactMessageNotifier
{
    public function __construct(
        private string $fromAddress,
        private string $toAddress,
        private MailerInterface $mailer,
    ) {}

    public function notify(
        SubmitContactMessageCommand $command,
        SubmitContactMessageResult $result,
    ): void {
        $email = new TemplatedEmail()
            ->from($this->fromAddress)
            ->to($this->toAddress)
            ->subject('New submission from the contact form')
            ->textTemplate('contact/submit_contact_message/emails/new_contact_notification.txt.twig')
            ->context([
                'id' => $result->contactMessageId,
                'fullName' => $command->fullName,
                'contactEmail' => $command->email,
                'subject' => $command->subject,
                'message' => $command->message,
                'createdAt' => $result->submittedAt,
            ])
        ;

        $this->mailer->send($email);
    }
}
