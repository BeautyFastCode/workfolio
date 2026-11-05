<?php

declare(strict_types=1);

namespace App\Tests\Contact\SubmitContactMessage\Infrastructure;

use App\Contact\SubmitContactMessage\Application\SubmitContactMessageCommand;
use App\Contact\SubmitContactMessage\Application\SubmitContactMessageResult;
use App\Contact\SubmitContactMessage\Infrastructure\MailerContactMessageNotifier;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\RawMessage;

/**
 * @internal
 *
 * @coversNothing
 */
final class MailerContactMessageNotifierTest extends TestCase
{
    public function test_it_builds_the_contact_notification_with_submission_details(): void
    {
        $email = null;
        $mailer = $this->createMock(MailerInterface::class);
        $mailer
            ->expects(self::once())
            ->method('send')
            ->willReturnCallback(static function (RawMessage $message) use (&$email): void {
                $email = $message;
            })
        ;

        $command = self::command();
        $result = self::submissionResult();
        new MailerContactMessageNotifier(
            'app@workfolio.test',
            'contact@workfolio.test',
            $mailer,
        )->notify($command, $result);

        self::assertInstanceOf(TemplatedEmail::class, $email);
        self::assertSame('New submission from the contact form', $email->getSubject());
        self::assertSame('app@workfolio.test', $email->getFrom()[0]->getAddress());
        self::assertSame('contact@workfolio.test', $email->getTo()[0]->getAddress());
        self::assertSame('contact/submit_contact_message/emails/new_contact_notification.txt.twig', $email->getTextTemplate());
        self::assertSame([
            'id' => $result->contactMessageId,
            'fullName' => $command->fullName,
            'contactEmail' => $command->email,
            'subject' => $command->subject,
            'message' => $command->message,
            'createdAt' => $result->submittedAt,
        ], $email->getContext());
    }

    public function test_it_propagates_a_mailer_failure_to_the_application_handler(): void
    {
        $failure = new \RuntimeException('Mailer unavailable.');
        $mailer = $this->createStub(MailerInterface::class);
        $mailer->method('send')->willThrowException($failure);

        $notifier = new MailerContactMessageNotifier(
            'app@workfolio.test',
            'contact@workfolio.test',
            $mailer,
        );

        $this->expectExceptionObject($failure);

        $notifier->notify(self::command(), self::submissionResult());
    }

    private static function command(): SubmitContactMessageCommand
    {
        return new SubmitContactMessageCommand(
            fullName: 'Ada Lovelace',
            email: 'ada@example.com',
            subject: 'Infrastructure test',
            message: 'A contact message sent through the new adapter.',
        );
    }

    private static function submissionResult(): SubmitContactMessageResult
    {
        return new SubmitContactMessageResult(
            contactMessageId: '0198b77f-1964-702e-881f-54f7ae39c45d',
            submittedAt: new \DateTimeImmutable('2026-08-04 12:00:00+02:00'),
        );
    }
}
