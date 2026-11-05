<?php

declare(strict_types=1);

namespace App\Tests\Contact\Shared\Infrastructure\Fixtures;

use App\Contact\Shared\Infrastructure\Fixtures\ContactLinkFixtures;
use App\Contact\Shared\Infrastructure\Persistence\Doctrine\Entity\ContactLink;
use Doctrine\Bundle\FixturesBundle\Loader\FixturesProvider;
use Doctrine\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class ContactLinkFixturesTest extends KernelTestCase
{
    public function test_fixture_remains_registered_with_the_same_group_and_data(): void
    {
        self::bootKernel();

        $fixturesProvider = self::getContainer()->get('doctrine.fixtures.loader');
        self::assertInstanceOf(FixturesProvider::class, $fixturesProvider);

        $fixtures = $fixturesProvider->getFixtures(['contact_link']);
        self::assertCount(1, $fixtures);
        $fixture = $fixtures[0];
        self::assertInstanceOf(ContactLinkFixtures::class, $fixture);
        self::assertSame(['contact_link'], ContactLinkFixtures::getGroups());

        $persisted = [];
        $manager = $this->createMock(ObjectManager::class);
        $manager
            ->expects($this->exactly(3))
            ->method('persist')
            ->willReturnCallback(static function (object $entity) use (&$persisted): void {
                $persisted[] = $entity;
            })
        ;
        $manager->expects($this->once())->method('flush');

        $fixture->load($manager);

        self::assertContainsOnlyInstancesOf(ContactLink::class, $persisted);
        self::assertSame([
            ['E-mail', 'fa-solid fa-envelope', 'mailto:office@gmail.com'],
            ['Github', 'fa-brands fa-github', 'https://github.com/SuperRepo'],
            ['LinkedIn', 'fa-brands fa-linkedin', 'https://www.linkedin.com/in/name-surname/'],
        ], array_map(
            static fn (ContactLink $contactLink): array => [
                $contactLink->getName(),
                $contactLink->getIcon(),
                $contactLink->getHref(),
            ],
            $persisted,
        ));
        self::assertSame([0, 1, 2], array_map(
            static fn (ContactLink $contactLink): int => $contactLink->getPosition(),
            $persisted,
        ));
        self::assertSame([true, true, true], array_map(
            static fn (ContactLink $contactLink): bool => $contactLink->isEnabled(),
            $persisted,
        ));
    }
}
