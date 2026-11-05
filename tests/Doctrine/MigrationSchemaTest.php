<?php

declare(strict_types=1);

namespace App\Tests\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\SchemaValidator;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 *
 * @coversNothing
 */
final class MigrationSchemaTest extends KernelTestCase
{
    public function test_migrations_create_the_schema_described_by_doctrine_metadata(): void
    {
        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->setAutoExit(false);

        $command = $application->find('doctrine:migrations:migrate');
        $exitCode = new CommandTester($command)->execute([
            '--no-interaction' => true,
        ]);

        self::assertSame(Command::SUCCESS, $exitCode);

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);

        $validator = new SchemaValidator($entityManager);

        self::assertSame([], $validator->validateMapping());

        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        self::assertSame(
            ['DROP TABLE doctrine_migration_versions'],
            new SchemaTool($entityManager)->getUpdateSchemaSql($metadata),
        );
    }
}
