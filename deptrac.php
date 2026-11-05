<?php

declare(strict_types=1);

use Deptrac\Deptrac\Contract\Config\Collector\BoolConfig;
use Deptrac\Deptrac\Contract\Config\Collector\ClassLikeConfig;
use Deptrac\Deptrac\Contract\Config\DeptracConfig;
use Deptrac\Deptrac\Contract\Config\Layer;
use Deptrac\Deptrac\Contract\Config\Ruleset;

return static function (DeptracConfig $config): void {
    $businessModules = ['Content', 'Portfolio', 'Contact', 'CallToAction', 'Identity'];
    $modules = [...$businessModules, 'Shared'];
    $modulePattern = '(?:'.implode('|', $modules).')';
    $sliceFirstPattern = static fn (string $layer): string => sprintf(
        '^App\%s\[^\]+\%s(?:\|$)',
        $modulePattern,
        $layer,
    );
    $sharedDoctrinePersistencePattern = '^App\Shared\Infrastructure\Persistence\Doctrine(?:\|$)';

    $domain = Layer::withName('Domain')->collectors(
        ClassLikeConfig::create($sliceFirstPattern('Domain')),
        ClassLikeConfig::create('^App\Shared\Domain(?:\|$)'),
    );
    $application = Layer::withName('Application')->collectors(
        ClassLikeConfig::create($sliceFirstPattern('Application')),
    );
    $sharedDoctrinePersistence = Layer::withName('Shared Doctrine persistence')->collectors(
        ClassLikeConfig::create($sharedDoctrinePersistencePattern),
    );
    $ui = Layer::withName('UI')->collectors(
        BoolConfig::create(
            must: [ClassLikeConfig::create($sliceFirstPattern('UI'))],
            mustNot: [
                ClassLikeConfig::create("^App\\{$modulePattern}\\[^\\]+\\UI\\Admin(?:\\|$)"),
            ],
        ),
        BoolConfig::create(
            must: [ClassLikeConfig::create('^App\Shared\UI(?:\|$)')],
            mustNot: [
                ClassLikeConfig::create('^App\Shared\UI\Admin(?:\|$)'),
            ],
        ),
    );
    $symfony = Layer::withName('Symfony')->collectors(
        ClassLikeConfig::create('^(?:Symfony\|EasyCorp\Bundle\EasyAdminBundle\|Psr\Log\)'),
    );
    $doctrine = Layer::withName('Doctrine')->collectors(
        ClassLikeConfig::create('^Doctrine\(?:.+|$)'),
    );
    $twig = Layer::withName('Twig')->collectors(
        ClassLikeConfig::create('^Twig\(?:.+|$)'),
    );

    $php = Layer::withName('PHP')->collectors(
        ClassLikeConfig::create('^SortDirection$'),
    );

    /** @var array<string, Layer> $moduleInfrastructureLayers */
    $moduleInfrastructureLayers = [];

    /** @var array<string, Layer> $moduleAdminLayers */
    $moduleAdminLayers = [];

    foreach ($modules as $module) {
        $infrastructureCollectors = [
            ClassLikeConfig::create("^App\\{$module}\\[^\\]+\\Infrastructure(?:\\|$)"),
        ];
        $adminCollectors = [
            ClassLikeConfig::create("^App\\{$module}\\[^\\]+\\UI\\Admin(?:\\|$)"),
        ];

        if ($module === 'Shared') {
            $infrastructureCollectors[] = BoolConfig::create(
                must: [ClassLikeConfig::create('^App\Shared\Infrastructure(?:\|$)')],
                mustNot: [ClassLikeConfig::create($sharedDoctrinePersistencePattern)],
            );
            $adminCollectors[] = ClassLikeConfig::create('^App\Shared\UI\Admin(?:\|$)');
        }

        $moduleInfrastructureLayers[$module] = Layer::withName("{$module} Infrastructure")->collectors(
            ...$infrastructureCollectors,
        );
        $moduleAdminLayers[$module] = Layer::withName("{$module} EasyAdmin UI")->collectors(
            ...$adminCollectors,
        );
    }

    $rulesets = [
        Ruleset::forLayer($domain)->accesses($domain),
        Ruleset::forLayer($application)->accesses($domain, $application),
        Ruleset::forLayer($sharedDoctrinePersistence)->accesses(
            $domain,
            $sharedDoctrinePersistence,
            $symfony,
            $doctrine,
        ),
        Ruleset::forLayer($ui)->accesses($domain, $application, $ui, $symfony, $twig),
    ];

    foreach ($modules as $module) {
        $infrastructure = $moduleInfrastructureLayers[$module];
        $admin = $moduleAdminLayers[$module];
        $adminDependencies = [
            $domain,
            $application,
            $sharedDoctrinePersistence,
            $infrastructure,
            $admin,
            $moduleAdminLayers['Shared'],
            $symfony,
            $doctrine,
            $twig,
        ];

        if ($module === 'Shared') {
            array_push($adminDependencies, ...array_values($moduleAdminLayers));
        }

        $rulesets[] = Ruleset::forLayer($infrastructure)->accesses(
            $domain,
            $application,
            $sharedDoctrinePersistence,
            $infrastructure,
            $symfony,
            $doctrine,
            $twig,
            $php,
        );
        $rulesets[] = Ruleset::forLayer($admin)->accesses(...$adminDependencies);
    }

    $config
        ->paths(__DIR__.'/src')
        ->cacheFile(__DIR__.'/var/cache/deptrac.cache')
        ->layers(
            $domain,
            $application,
            $sharedDoctrinePersistence,
            $ui,
            $symfony,
            $doctrine,
            $twig,
            $php,
            ...array_values($moduleInfrastructureLayers),
            ...array_values($moduleAdminLayers),
        )
        ->rulesets(...$rulesets)
    ;
};
