<?php

declare(strict_types=1);

namespace App\Tests\Contact\SubmitContactMessage\Application;

use App\Contact\SubmitContactMessage\Application\ContactMessageNotificationFailed;
use App\Contact\SubmitContactMessage\Application\ContactMessageNotifier;
use App\Contact\SubmitContactMessage\Application\ContactMessagePersistenceFailed;
use App\Contact\SubmitContactMessage\Application\ContactMessageRepository;
use App\Contact\SubmitContactMessage\Application\ContactMessageTransaction;
use App\Contact\SubmitContactMessage\Application\ContactMessageValidator;
use App\Contact\SubmitContactMessage\Application\InvalidContactMessage;
use App\Contact\SubmitContactMessage\Application\SubmitContactMessageCommand;
use App\Contact\SubmitContactMessage\Application\SubmitContactMessageHandler;
use App\Contact\SubmitContactMessage\Application\SubmitContactMessageResult;
use App\Shared\Observability\Application\HandlerMetrics;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class SubmitContactMessageHandlerTest extends TestCase
{
    public function test_it_commits_the_message_before_requesting_the_notification(): void
    {
        $command = self::validCommand();
        $result = self::submissionResult();
        $events = [];

        $repository = $this->createMock(ContactMessageRepository::class);
        $repository
            ->expects(self::once())
            ->method('save')
            ->with($command)
            ->willReturnCallback(static function () use (&$events, $result): SubmitContactMessageResult {
                $events[] = 'message.saved';

                return $result;
            })
        ;

        $transaction = $this->createMock(ContactMessageTransaction::class);
        $transaction
            ->expects(self::once())
            ->method('execute')
            ->willReturnCallback(
                static function (\Closure $operation) use (&$events): SubmitContactMessageResult {
                    $events[] = 'transaction.started';
                    $operationResult = $operation();
                    self::assertInstanceOf(SubmitContactMessageResult::class, $operationResult);
                    $events[] = 'transaction.committed';

                    return $operationResult;
                },
            )
        ;

        $notifier = $this->createMock(ContactMessageNotifier::class);
        $notifier
            ->expects(self::once())
            ->method('notify')
            ->with($command, $result)
            ->willReturnCallback(static function () use (&$events): void {
                $events[] = 'notification.requested';
            })
        ;

        $metrics = $this->createMock(HandlerMetrics::class);
        $metrics
            ->expects(self::once())
            ->method('measure')
            ->with('contact.submit_contact_message', self::isCallable())
            ->willReturnCallback(
                static fn (string $_handler, callable $operation): mixed => $operation(),
            )
        ;
        $actualResult = $this->handler($repository, $transaction, $notifier, $metrics)($command);

        self::assertSame($result, $actualResult);
        self::assertSame([
            'transaction.started',
            'message.saved',
            'transaction.committed',
            'notification.requested',
        ], $events);
    }

    public function test_it_rejects_an_invalid_message_before_starting_a_transaction(): void
    {
        $repository = $this->createMock(ContactMessageRepository::class);
        $repository->expects(self::never())->method('save');
        $transaction = $this->createMock(ContactMessageTransaction::class);
        $transaction->expects(self::never())->method('execute');
        $notifier = $this->createMock(ContactMessageNotifier::class);
        $notifier->expects(self::never())->method('notify');

        $command = new SubmitContactMessageCommand(
            fullName: ' ',
            email: 'invalid-email',
            subject: 'S',
            message: 'short',
        );

        try {
            $this->handler($repository, $transaction, $notifier)($command);
            self::fail('Expected an invalid contact message error.');
        } catch (InvalidContactMessage $exception) {
            self::assertSame([
                'fullName' => ['required'],
                'email' => ['invalid'],
                'subject' => ['too_short'],
                'message' => ['too_short'],
            ], $exception->violations);
        }
    }

    public function test_it_does_not_notify_when_the_transaction_cannot_be_committed(): void
    {
        $command = self::validCommand();
        $result = self::submissionResult();
        $commitFailure = new \RuntimeException('Commit failed.');

        $repository = $this->createMock(ContactMessageRepository::class);
        $repository
            ->expects(self::once())
            ->method('save')
            ->with($command)
            ->willReturn($result)
        ;

        $transaction = $this->createMock(ContactMessageTransaction::class);
        $transaction
            ->expects(self::once())
            ->method('execute')
            ->willReturnCallback(
                static function (\Closure $operation) use ($commitFailure): never {
                    $operation();

                    throw $commitFailure;
                },
            )
        ;

        $notifier = $this->createMock(ContactMessageNotifier::class);
        $notifier->expects(self::never())->method('notify');

        try {
            $this->handler($repository, $transaction, $notifier)($command);
            self::fail('Expected a persistence error.');
        } catch (ContactMessagePersistenceFailed $exception) {
            self::assertSame($commitFailure, $exception->getPrevious());
        }
    }

    public function test_notification_failure_exposes_the_committed_message_for_a_separate_retry(): void
    {
        $command = self::validCommand();
        $result = self::submissionResult();
        $notificationFailure = new \RuntimeException('Mailer unavailable.');

        $repository = $this->createStub(ContactMessageRepository::class);
        $repository->method('save')->willReturn($result);

        $transaction = $this->createStub(ContactMessageTransaction::class);
        $transaction
            ->method('execute')
            ->willReturnCallback(static function (\Closure $operation): SubmitContactMessageResult {
                $operationResult = $operation();
                self::assertInstanceOf(SubmitContactMessageResult::class, $operationResult);

                return $operationResult;
            })
        ;

        $notifier = $this->createMock(ContactMessageNotifier::class);
        $notifier
            ->expects(self::once())
            ->method('notify')
            ->with($command, $result)
            ->willThrowException($notificationFailure)
        ;

        try {
            $this->handler($repository, $transaction, $notifier)($command);
            self::fail('Expected a notification error.');
        } catch (ContactMessageNotificationFailed $exception) {
            self::assertSame($command, $exception->command);
            self::assertSame($result, $exception->result);
            self::assertSame($notificationFailure, $exception->getPrevious());
        }
    }

    private function handler(
        ContactMessageRepository $repository,
        ContactMessageTransaction $transaction,
        ContactMessageNotifier $notifier,
        ?HandlerMetrics $metrics = null,
    ): SubmitContactMessageHandler {
        return new SubmitContactMessageHandler(
            new ContactMessageValidator(),
            $repository,
            $transaction,
            $notifier,
            $metrics ?? $this->passThroughMetrics(),
        );
    }

    private function passThroughMetrics(): HandlerMetrics
    {
        $metrics = $this->createStub(HandlerMetrics::class);
        $metrics
            ->method('measure')
            ->willReturnCallback(
                static fn (string $_handler, callable $operation): mixed => $operation(),
            )
        ;

        return $metrics;
    }

    private static function validCommand(): SubmitContactMessageCommand
    {
        return new SubmitContactMessageCommand(
            fullName: 'Ada Lovelace',
            email: 'ada@example.com',
            subject: 'A valid subject',
            message: 'A valid contact message.',
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
