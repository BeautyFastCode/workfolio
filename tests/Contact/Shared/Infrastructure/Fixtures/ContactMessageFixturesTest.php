<?php

declare(strict_types=1);

namespace App\Tests\Contact\Shared\Infrastructure\Fixtures;

use App\Contact\Shared\Infrastructure\Fixtures\ContactMessageFixtures;
use App\Contact\Shared\Infrastructure\Persistence\Doctrine\Entity\ContactMessage;
use Doctrine\Bundle\FixturesBundle\Loader\FixturesProvider;
use Doctrine\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class ContactMessageFixturesTest extends KernelTestCase
{
    public function test_fixture_remains_registered_with_the_same_group_and_data(): void
    {
        self::bootKernel();

        $fixturesProvider = self::getContainer()->get('doctrine.fixtures.loader');
        self::assertInstanceOf(FixturesProvider::class, $fixturesProvider);

        $fixtures = $fixturesProvider->getFixtures(['contact_message']);
        self::assertCount(1, $fixtures);
        $fixture = $fixtures[0];
        self::assertInstanceOf(ContactMessageFixtures::class, $fixture);
        self::assertSame(['contact_message'], ContactMessageFixtures::getGroups());

        $persisted = [];
        $manager = $this->createMock(ObjectManager::class);
        $manager
            ->expects($this->exactly(10))
            ->method('persist')
            ->willReturnCallback(static function (object $entity) use (&$persisted): void {
                $persisted[] = $entity;
            })
        ;
        $manager->expects($this->once())->method('flush');

        $fixture->load($manager);

        self::assertContainsOnlyInstancesOf(ContactMessage::class, $persisted);
        self::assertSame([
            'Anna Kowalska',
            'anna.kowalska@example.com',
            'Pytanie o ofertę',
            'Dzień dobry, chciałabym dowiedzieć się więcej o dostępnych usługach i cenniku.',
        ], self::messageData($persisted[0]));
        self::assertSame([
            'Daniel Wilson',
            'daniel.wilson@example.com',
            'General inquiry',
            'I have an additional question that I could not find in the FAQ section. Please reply by email.',
        ], self::messageData($persisted[9]));
    }

    /** @return array{string, string, string, string} */
    private static function messageData(ContactMessage $message): array
    {
        return [
            $message->getFullName(),
            $message->getEmail(),
            $message->getSubject(),
            $message->getMessage(),
        ];
    }
}
