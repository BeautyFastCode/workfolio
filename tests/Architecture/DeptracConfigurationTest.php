<?php

declare(strict_types=1);

namespace App\Tests\Architecture;

use Deptrac\Deptrac\Contract\Config\DeptracConfig;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class DeptracConfigurationTest extends TestCase
{
    /**
     * @param list<string> $expectedLayers
     */
    #[DataProvider('provideRepresentativeTokens')]
    public function test_collectors_cover_only_the_target_module_layout(string $token, array $expectedLayers): void
    {
        $configuration = self::configuration();

        self::assertSame($expectedLayers, self::matchingLayers($configuration['layers'], $token));
    }

    /**
     * @return iterable<string, array{string, list<string>}>
     */
    public static function provideRepresentativeTokens(): iterable
    {
        yield 'PHP sort direction' => [
            'SortDirection',
            ['PHP'],
        ];

        yield 'layer-first Application is rejected' => [
            'App\Contact\Application\SubmitContactMessage\SubmitContactMessageHandler',
            [],
        ];

        yield 'slice-first Application' => [
            'App\Contact\SubmitContactMessage\Application\SubmitContactMessageHandler',
            ['Application'],
        ];

        yield 'module Shared Application' => [
            'App\Contact\Shared\Application\ContactPolicy',
            ['Application'],
        ];

        yield 'layer-first Infrastructure is rejected' => [
            'App\Contact\Infrastructure\SubmitContactMessage\MailerContactMessageNotifier',
            [],
        ];

        yield 'slice-first Infrastructure' => [
            'App\Contact\SubmitContactMessage\Infrastructure\MailerContactMessageNotifier',
            ['Contact Infrastructure'],
        ];

        yield 'module Shared Infrastructure' => [
            'App\Contact\Shared\Infrastructure\Persistence\ContactMessage',
            ['Contact Infrastructure'],
        ];

        yield 'layer-first UI is rejected' => [
            'App\Contact\UI\SubmitContactMessage\ContactFormController',
            [],
        ];

        yield 'slice-first UI' => [
            'App\Contact\SubmitContactMessage\UI\ContactFormController',
            ['UI'],
        ];

        yield 'layer-first EasyAdmin is rejected' => [
            'App\Contact\UI\Admin\ContactMessageCrudController',
            [],
        ];

        yield 'slice-first EasyAdmin' => [
            'App\Contact\ManageContactMessages\UI\Admin\ContactMessageCrudController',
            ['Contact EasyAdmin UI'],
        ];

        yield 'cross-module Shared slice' => [
            'App\Shared\Observability\Application\HandlerMetrics',
            ['Application'],
        ];

        yield 'cross-module Shared technical UI' => [
            'App\Shared\UI\Admin\DashboardController',
            ['Shared EasyAdmin UI'],
        ];

        yield 'cross-module Shared technical persistence' => [
            'App\Shared\Infrastructure\Persistence\Doctrine\Entity\BaseEntity',
            ['Shared Doctrine persistence'],
        ];
    }

    public function test_application_cannot_access_framework_layers(): void
    {
        $configuration = self::configuration();
        $applicationRules = $configuration['ruleset']['Application'];
        unset($applicationRules['name']);

        self::assertSame(['Domain', 'Application'], array_values($applicationRules));
    }

    /**
     * @return array{layers: array<string, array{name: string, collectors: list<array<string, mixed>>}>, ruleset: array<string, array<array-key, string>>}
     */
    private static function configuration(): array
    {
        $configure = require dirname(__DIR__, 2).'/deptrac.php';

        if (!is_callable($configure)) {
            throw new \RuntimeException('Deptrac configuration must return a callable.');
        }

        $config = new DeptracConfig();
        $configure($config);

        /** @var array{layers: array<string, array{name: string, collectors: list<array<string, mixed>>}>, ruleset: array<string, array<array-key, string>>} $configuration */
        $configuration = $config->toArray();

        return $configuration;
    }

    /**
     * @param array<string, array{name: string, collectors: list<array<string, mixed>>}> $layers
     *
     * @return list<string>
     */
    private static function matchingLayers(array $layers, string $token): array
    {
        $matches = [];

        foreach ($layers as $layer) {
            foreach ($layer['collectors'] as $collector) {
                if (self::collectorMatches($collector, $token)) {
                    $matches[] = $layer['name'];

                    break;
                }
            }
        }

        return $matches;
    }

    /**
     * @param array<string, mixed> $collector
     */
    private static function collectorMatches(array $collector, string $token): bool
    {
        if ($collector['type'] === 'classLike') {
            $pattern = $collector['value'] ?? null;

            if (!is_string($pattern)) {
                throw new \RuntimeException('A classLike collector must contain a string value.');
            }

            return preg_match('/'.$pattern.'/i', $token) === 1;
        }

        if ($collector['type'] !== 'bool') {
            return false;
        }

        /** @var list<array<string, mixed>> $must */
        $must = $collector['must'];

        /** @var list<array<string, mixed>> $mustNot */
        $mustNot = $collector['must_not'];

        return array_all($must, static fn (array $nested): bool => self::collectorMatches($nested, $token))
            && array_all($mustNot, static fn (array $nested): bool => !self::collectorMatches($nested, $token));
    }
}
