<?php

declare(strict_types=1);

namespace App\Tests\CallToAction\PublishRandomCta\UI;

use App\CallToAction\PublishRandomCta\UI\Scheduler\PublishCtaMessage;
use App\CallToAction\PublishRandomCta\UI\Scheduler\Schedule;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Scheduler\Generator\MessageContext;
use Symfony\Component\Uid\UuidV7;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * @internal
 *
 * @covers \App\CallToAction\PublishRandomCta\UI\Scheduler\Schedule
 */
final class ScheduleTest extends TestCase
{
    public function test_it_preserves_the_five_second_schedule_and_message_payload(): void
    {
        $cache = $this->createStub(CacheInterface::class);
        $schedule = new Schedule($cache)->getSchedule();
        $recurringMessages = $schedule->getRecurringMessages();

        self::assertNull($schedule->getState());
        self::assertFalse($schedule->shouldProcessOnlyLastMissedRun());
        self::assertCount(1, $recurringMessages);

        $recurringMessage = $recurringMessages[0];
        $trigger = $recurringMessage->getTrigger();
        self::assertSame('every 5 seconds', (string) $trigger);
        $messages = [...$recurringMessage->getMessages(new MessageContext(
            'default',
            $recurringMessage->getId(),
            $trigger,
            new \DateTimeImmutable(),
        ))];

        self::assertCount(1, $messages);
        self::assertInstanceOf(PublishCtaMessage::class, $messages[0]);
        self::assertInstanceOf(UuidV7::class, $messages[0]->getId());
    }
}
