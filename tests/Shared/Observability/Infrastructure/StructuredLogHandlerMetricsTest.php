<?php

declare(strict_types=1);

namespace App\Tests\Shared\Observability\Infrastructure;

use App\Shared\Observability\Infrastructure\StructuredLogHandlerMetrics;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @internal
 *
 * @covers \App\Shared\Observability\Infrastructure\StructuredLogHandlerMetrics
 */
final class StructuredLogHandlerMetricsTest extends TestCase
{
    public function test_it_records_handler_duration_and_success(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(self::once())
            ->method('log')
            ->with('info', 'Application handler measured.', [
                'handler' => 'contact.submit_contact_message',
                'outcome' => 'success',
                'duration_ms' => 2.5,
                'errors' => 0,
            ])
        ;
        $metrics = new StructuredLogHandlerMetrics($logger, self::clock(1_000_000, 3_500_000));

        $result = $metrics->measure(
            'contact.submit_contact_message',
            static fn (): string => self::successfulResult(),
        );

        self::assertSame('result', $result);
    }

    public function test_it_records_handler_duration_and_error_then_rethrows_the_same_exception(): void
    {
        $failure = new \RuntimeException('Handler failed.');
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(self::once())
            ->method('log')
            ->with('error', 'Application handler measured.', [
                'handler' => 'call_to_action.publish_random_cta',
                'outcome' => 'error',
                'duration_ms' => 1.25,
                'errors' => 1,
                'error_type' => \RuntimeException::class,
            ])
        ;
        $metrics = new StructuredLogHandlerMetrics($logger, self::clock(2_000_000, 3_250_000));

        try {
            $metrics->measure('call_to_action.publish_random_cta', static function () use ($failure): void {
                self::throwFailure($failure);
            });
            self::fail('Expected the measured failure.');
        } catch (\RuntimeException $error) {
            self::assertSame($failure, $error);
        }
    }

    public function test_logging_failure_does_not_change_a_successful_handler_result(): void
    {
        $logger = $this->createStub(LoggerInterface::class);
        $logger->method('log')->willThrowException(new \RuntimeException('Logger unavailable.'));
        $metrics = new StructuredLogHandlerMetrics($logger, self::clock(5_000_000, 4_000_000));

        self::assertSame(
            42,
            $metrics->measure('identity.create_admin_user', static fn (): int => self::successfulIntegerResult()),
        );
    }

    public function test_logging_failure_does_not_replace_the_handler_exception(): void
    {
        $handlerFailure = new \LogicException('Original failure.');
        $logger = $this->createStub(LoggerInterface::class);
        $logger->method('log')->willThrowException(new \RuntimeException('Logger unavailable.'));
        $metrics = new StructuredLogHandlerMetrics($logger, self::clock(1, 2));

        try {
            $metrics->measure('identity.create_admin_user', static function () use ($handlerFailure): void {
                self::throwFailure($handlerFailure);
            });
            self::fail('Expected the measured failure.');
        } catch (\LogicException $error) {
            self::assertSame($handlerFailure, $error);
        }
    }

    /**
     * @return \Closure(): int
     */
    private static function clock(int ...$times): \Closure
    {
        return static function () use (&$times): int {
            $time = array_shift($times);
            self::assertIsInt($time);

            return $time;
        };
    }

    private static function successfulResult(): string
    {
        return 'result';
    }

    private static function successfulIntegerResult(): int
    {
        return 42;
    }

    private static function throwFailure(\Throwable $failure): void
    {
        throw $failure;
    }
}
