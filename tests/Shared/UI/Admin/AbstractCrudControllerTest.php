<?php

declare(strict_types=1);

namespace App\Tests\Shared\UI\Admin;

use App\Contact\Shared\Infrastructure\Persistence\Doctrine\Entity\ContactLink;
use App\Shared\UI\Admin\AbstractCrudController;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class AbstractCrudControllerTest extends TestCase
{
    public function test_persist_reinitializes_creation_time_before_using_easy_admin_persistence(): void
    {
        $controller = $this->createController();
        $entity = new ContactLink();
        $originalDate = new \DateTimeImmutable('2000-01-01 00:00:00');
        $entity->setCreatedAt($originalDate);
        $entityManager = $this->createEntityManagerExpectingPersistenceOf($entity);

        $controller->persistEntity($entityManager, $entity);

        self::assertGreaterThan($originalDate, $entity->getCreatedAt());
    }

    public function test_update_touches_entity_before_using_easy_admin_persistence(): void
    {
        $controller = $this->createController();
        $entity = new ContactLink();
        $entityManager = $this->createEntityManagerExpectingPersistenceOf($entity);

        $controller->updateEntity($entityManager, $entity);

        self::assertInstanceOf(\DateTimeImmutable::class, $entity->getUpdatedAt());
    }

    public function test_non_timestamped_entity_uses_easy_admin_persistence(): void
    {
        $controller = $this->createController();
        $entity = new \stdClass();
        $entityManager = $this->createEntityManagerExpectingPersistenceOf($entity);

        $controller->persistEntity($entityManager, $entity);
    }

    public function test_non_timestamped_entity_uses_easy_admin_update(): void
    {
        $controller = $this->createController();
        $entity = new \stdClass();
        $entityManager = $this->createEntityManagerExpectingPersistenceOf($entity);

        $controller->updateEntity($entityManager, $entity);
    }

    /**
     * @return AbstractCrudController<object>
     */
    private function createController(): AbstractCrudController
    {
        return new class extends AbstractCrudController {
            public static function getEntityFqcn(): string
            {
                return \stdClass::class;
            }
        };
    }

    private function createEntityManagerExpectingPersistenceOf(object $entity): EntityManagerInterface
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist')->with($entity);
        $entityManager->expects(self::once())->method('flush');

        return $entityManager;
    }
}
