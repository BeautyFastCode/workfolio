<?php

declare(strict_types=1);

namespace App\Tests\Architecture;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * @internal
 *
 * @coversNothing
 */
final class MainServicesConfigurationTest extends TestCase
{
    private const array EXPECTED_IMPORTS = [
        '../src/Shared/services.yaml',
        '../src/CallToAction/services.yaml',
        '../src/Contact/services.yaml',
        '../src/Content/services.yaml',
        '../src/Portfolio/services.yaml',
        '../src/Identity/services.yaml',
    ];

    private const array EXPECTED_PARAMETERS = [
        'app.image.base_path',
        'app.image.upload_dir',
        'app.supported_locales',
    ];

    public function test_main_services_file_is_only_the_composition_root(): void
    {
        $configuration = Yaml::parseFile(dirname(__DIR__, 2).'/config/services.yaml');

        self::assertIsArray($configuration);
        self::assertSame([], self::compositionRootViolations($configuration));
    }

    public function test_guard_reports_invalid_composition_root(): void
    {
        self::assertSame(
            [
                'Missing module services import: ../src/Contact/services.yaml.',
                'Module service definition is forbidden in config/services.yaml: App\Contact\NewHandler.',
                'Module-specific parameter is forbidden in config/services.yaml: mailer.from_address.',
                'Unexpected services import: ../src/Unknown/services.yaml.',
            ],
            self::compositionRootViolations([
                'imports' => array_map(
                    static fn (string $resource): array => ['resource' => $resource],
                    [
                        ...array_diff(self::EXPECTED_IMPORTS, ['../src/Contact/services.yaml']),
                        '../src/Unknown/services.yaml',
                    ],
                ),
                'parameters' => [
                    ...array_fill_keys(self::EXPECTED_PARAMETERS, 'value'),
                    'mailer.from_address' => 'value',
                ],
                'services' => [
                    'App\Contact\NewHandler' => null,
                ],
            ]),
        );
    }

    /**
     * @param array<mixed> $configuration
     *
     * @return list<string>
     */
    private static function compositionRootViolations(array $configuration): array
    {
        $imports = [];
        $violations = [];

        foreach (is_array($configuration['imports'] ?? null) ? $configuration['imports'] : [] as $import) {
            $resource = is_array($import) ? ($import['resource'] ?? null) : $import;

            if (is_string($resource)) {
                $imports[] = $resource;
            } else {
                $violations[] = 'Invalid services import entry.';
            }
        }

        $parameters = array_values(array_filter(
            array_keys(is_array($configuration['parameters'] ?? null) ? $configuration['parameters'] : []),
            is_string(...),
        ));
        $services = array_values(array_filter(
            array_keys(is_array($configuration['services'] ?? null) ? $configuration['services'] : []),
            is_string(...),
        ));

        foreach (array_diff(self::EXPECTED_IMPORTS, $imports) as $resource) {
            $violations[] = sprintf('Missing module services import: %s.', $resource);
        }

        foreach (array_diff($imports, self::EXPECTED_IMPORTS) as $resource) {
            $violations[] = sprintf('Unexpected services import: %s.', (string) $resource);
        }

        foreach (array_diff($parameters, self::EXPECTED_PARAMETERS) as $parameter) {
            $violations[] = sprintf(
                'Module-specific parameter is forbidden in config/services.yaml: %s.',
                $parameter,
            );
        }

        foreach ($services as $service) {
            $violations[] = sprintf(
                'Module service definition is forbidden in config/services.yaml: %s.',
                $service,
            );
        }

        sort($violations);

        return $violations;
    }
}
