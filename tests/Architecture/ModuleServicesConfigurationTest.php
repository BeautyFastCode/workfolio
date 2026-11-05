<?php

declare(strict_types=1);

namespace App\Tests\Architecture;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * @internal
 *
 * @coversNothing
 */
final class ModuleServicesConfigurationTest extends TestCase
{
    private const array EXPECTED_EXCLUDES = [
        'CallToAction' => [
            'App\CallToAction\PublishRandomCta\UI\\' => [
                'PublishRandomCta/UI/Scheduler/PublishCtaMessage.php',
            ],
            'App\CallToAction\Shared\Infrastructure\\' => [
                'Shared/Infrastructure/Persistence/Doctrine/Entity/',
            ],
        ],
        'Contact' => [
            'App\Contact\SubmitContactMessage\UI\\' => [
                'SubmitContactMessage/UI/ContactMessageData.php',
            ],
            'App\Contact\Shared\Infrastructure\\' => [
                'Shared/Infrastructure/Persistence/',
            ],
        ],
        'Content' => [
            'App\Content\Shared\Infrastructure\\' => ['Shared/Infrastructure/Persistence/'],
        ],
        'Identity' => [
            'App\Identity\ManageAdminUsers\UI\\' => [
                'ManageAdminUsers/UI/Admin/AdminUserRoleLabels.php',
            ],
            'App\Identity\Shared\Infrastructure\\' => ['Shared/Infrastructure/Persistence/'],
        ],
        'Portfolio' => [
            'App\Portfolio\Shared\Infrastructure\\' => ['Shared/Infrastructure/Persistence/'],
        ],
        'Shared' => [
            'App\Shared\UI\\' => [
                'UI/Admin/LocaleLabels.php',
                'UI/TurboFrame.php',
            ],
        ],
    ];

    private const array EXPECTED_PARAMETERS = [
        'CallToAction' => [],
        'Contact' => ['mailer.from_address', 'mailer.to_address'],
        'Content' => [],
        'Identity' => [],
        'Portfolio' => [],
        'Shared' => ['umami.enabled', 'umami.url', 'umami.website_id'],
    ];

    #[DataProvider('moduleProvider')]
    public function test_module_owns_its_definitions_defaults_parameters_and_excludes(string $module): void
    {
        $configuration = self::loadConfiguration($module);

        self::assertSame([], self::moduleConfigurationViolations($module, $configuration));
        self::assertSame(
            self::EXPECTED_EXCLUDES[$module],
            self::configuredExcludes($configuration),
        );
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function moduleProvider(): iterable
    {
        foreach (array_keys(self::EXPECTED_PARAMETERS) as $module) {
            yield $module => [$module];
        }
    }

    public function test_each_application_port_has_an_alias_to_an_implementing_infrastructure_class(): void
    {
        foreach (array_keys(self::EXPECTED_PARAMETERS) as $module) {
            $services = self::services(self::loadConfiguration($module));
            $files = glob(dirname(__DIR__, 2).'/src/'.$module.'/*/Application/*.php');
            self::assertNotFalse($files);

            foreach ($files as $file) {
                $port = 'App\\'.$module.'\\'.basename(dirname($file, 2)).'\Application\\'.basename($file, '.php');

                if (!interface_exists($port)) {
                    continue;
                }

                self::assertArrayHasKey($port, $services, sprintf('Missing service for port %s.', $port));
                $definition = $services[$port];
                self::assertIsArray($definition);
                self::assertArrayHasKey('alias', $definition);
                $adapter = $definition['alias'];
                self::assertIsString($adapter);
                self::assertStringStartsWith('App\\'.$module.'\\', $adapter);
                self::assertStringContainsString('\Infrastructure\\', $adapter);
                self::assertTrue(is_a($adapter, $port, true), sprintf('%s must implement %s.', $adapter, $port));
            }
        }
    }

    public function test_guard_reports_foreign_definition_and_application_discovery(): void
    {
        self::assertSame(
            [
                'Contact/services.yaml has invalid _defaults.',
                'Contact/services.yaml registers a foreign service: App\Identity\ForeignService.',
                'Contact/services.yaml uses forbidden Application discovery: App\Contact\Application\.',
                'Unexpected parameter in Contact/services.yaml: foreign.parameter.',
            ],
            self::moduleConfigurationViolations('Contact', [
                'parameters' => ['foreign.parameter' => 'value'],
                'services' => [
                    '_defaults' => ['autowire' => true],
                    'App\Contact\Application\\' => ['resource' => 'Application/'],
                    'App\Identity\ForeignService' => null,
                ],
            ]),
        );
    }

    /**
     * @param array<mixed> $configuration
     *
     * @return list<string>
     */
    private static function moduleConfigurationViolations(string $module, array $configuration): array
    {
        $services = self::services($configuration);
        $defaults = $services['_defaults'] ?? null;
        $parameters = array_keys(
            is_array($configuration['parameters'] ?? null) ? $configuration['parameters'] : [],
        );
        $violations = [];

        if (['autowire' => true, 'autoconfigure' => true] !== $defaults) {
            $violations[] = sprintf('%s/services.yaml has invalid _defaults.', $module);
        }

        foreach (array_diff($parameters, self::EXPECTED_PARAMETERS[$module]) as $parameter) {
            $violations[] = sprintf('Unexpected parameter in %s/services.yaml: %s.', $module, $parameter);
        }

        foreach ($services as $id => $definition) {
            if ($id === '_defaults') {
                continue;
            }

            if (!str_starts_with($id, 'App\\'.$module.'\\')) {
                $violations[] = sprintf('%s/services.yaml registers a foreign service: %s.', $module, $id);
            }

            if (str_ends_with($id, '\Application\\') && is_array($definition) && isset($definition['resource'])) {
                $violations[] = sprintf(
                    '%s/services.yaml uses forbidden Application discovery: %s.',
                    $module,
                    $id,
                );
            }
        }

        sort($violations);

        return $violations;
    }

    /**
     * @param array<mixed> $configuration
     *
     * @return array<string, list<string>>
     */
    private static function configuredExcludes(array $configuration): array
    {
        $excludes = [];

        foreach (self::services($configuration) as $id => $definition) {
            if (is_array($definition) && isset($definition['exclude'])) {
                $excludes[$id] = array_values(array_filter(
                    (array) $definition['exclude'],
                    is_string(...),
                ));
            }
        }

        return $excludes;
    }

    /**
     * @param array<mixed> $configuration
     *
     * @return array<string, mixed>
     */
    private static function services(array $configuration): array
    {
        $services = [];

        foreach (is_array($configuration['services'] ?? null) ? $configuration['services'] : [] as $id => $definition) {
            if (is_string($id)) {
                $services[$id] = $definition;
            }
        }

        return $services;
    }

    /**
     * @return array<mixed>
     */
    private static function loadConfiguration(string $module): array
    {
        $configuration = Yaml::parseFile(dirname(__DIR__, 2).'/src/'.$module.'/services.yaml');

        self::assertIsArray($configuration);

        return $configuration;
    }
}
