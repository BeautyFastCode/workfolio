<?php

declare(strict_types=1);

namespace App\Contact\SubmitContactMessage\Application;

use App\Shared\Observability\Application\HandlerMetrics;

final readonly class SubmitContactMessageHandler
{
    private const string METRIC_NAME = 'contact.submit_contact_message';

    public function __construct(
        private ContactMessageValidator $validator,
        private ContactMessageRepository $repository,
        private ContactMessageTransaction $transaction,
        private ContactMessageNotifier $notifier,
        private HandlerMetrics $metrics,
    ) {}

    /**
     * @throws ContactMessageNotificationFailed when the message was committed but notification was not requested
     * @throws ContactMessagePersistenceFailed  when saving or committing the message failed
     * @throws InvalidContactMessage            when the command contains invalid input
     */
    public function __invoke(SubmitContactMessageCommand $command): SubmitContactMessageResult
    {
        return $this->metrics->measure(
            self::METRIC_NAME,
            fn (): SubmitContactMessageResult => $this->handle($command),
        );
    }

    private function handle(SubmitContactMessageCommand $command): SubmitContactMessageResult
    {
        $violations = $this->validator->validate($command);

        if ($violations !== []) {
            throw new InvalidContactMessage($violations);
        }

        try {
            $result = $this->transaction->execute(
                fn (): SubmitContactMessageResult => $this->repository->save($command),
            );
        } catch (\Throwable $exception) {
            throw new ContactMessagePersistenceFailed($exception);
        }

        try {
            $this->notifier->notify($command, $result);
        } catch (\Throwable $exception) {
            throw new ContactMessageNotificationFailed($command, $result, $exception);
        }

        return $result;
    }
}
