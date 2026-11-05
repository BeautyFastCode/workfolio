<?php

declare(strict_types=1);

namespace App\Shared\Observability\Infrastructure;

use App\Shared\Observability\Application\HandlerMetrics;
use Psr\Log\LoggerInterface;

final readonly class StructuredLogHandlerMetrics implements HandlerMetrics
{
    /** @var \Closure(): int */
    private \Closure $nanoseconds;

    /**
     * @param null|\Closure(): int $nanoseconds
     */
    public function __construct(
        private LoggerInterface $logger,
        ?\Closure $nanoseconds = null,
    ) {
        $this->nanoseconds = $nanoseconds ?? static fn (): int => hrtime(true);
    }

    public function measure(string $handler, callable $operation): mixed
    {
        $startedAt = ($this->nanoseconds)();

        try {
            $result = $operation();
        } catch (\Throwable $error) {
            $this->record($handler, $startedAt, 'error', $error);

            throw $error;
        }

        $this->record($handler, $startedAt, 'success');

        return $result;
    }

    private function record(
        string $handler,
        int $startedAt,
        string $outcome,
        ?\Throwable $error = null,
    ): void {
        $context = [
            'handler' => $handler,
            'outcome' => $outcome,
            'duration_ms' => max(0, ($this->nanoseconds)() - $startedAt) / 1_000_000,
            'errors' => $error instanceof \Throwable ? 1 : 0,
        ];

        if ($error instanceof \Throwable) {
            $context['error_type'] = $error::class;
        }

        try {
            $this->logger->log(
                $error instanceof \Throwable ? 'error' : 'info',
                'Application handler measured.',
                $context,
            );
        } catch (\Throwable) {
            // Observability must never alter the handler result or exception.
        }
    }
}
