<?php

declare(strict_types=1);

namespace App\Tests\Architecture;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class ModuleStructureConventionTest extends TestCase
{
    private const array BUSINESS_MODULES = [
        'CallToAction',
        'Contact',
        'Content',
        'Identity',
        'Portfolio',
    ];

    public function test_business_modules_do_not_contain_layer_first_directories(): void
    {
        $projectDirectory = dirname(__DIR__, 2);
        $directories = [];

        foreach (['src', 'tests'] as $root) {
            foreach (self::BUSINESS_MODULES as $module) {
                foreach (['Application', 'Infrastructure', 'UI'] as $layer) {
                    $relativeDirectory = sprintf('%s/%s/%s', $root, $module, $layer);

                    if (is_dir($projectDirectory.'/'.$relativeDirectory)) {
                        $directories[] = $relativeDirectory;
                    }
                }
            }
        }

        self::assertSame([], self::forbiddenDirectoryViolations($directories, 'Layer-first'));
    }

    public function test_guard_reports_layer_first_directories(): void
    {
        self::assertSame(
            [
                'Layer-first directory is forbidden: src/Contact/Application.',
                'Layer-first directory is forbidden: tests/Portfolio/UI.',
            ],
            self::forbiddenDirectoryViolations(
                ['tests/Portfolio/UI', 'src/Contact/Application'],
                'Layer-first',
            ),
        );
    }

    public function test_module_templates_do_not_return_to_legacy_global_directories(): void
    {
        $projectDirectory = dirname(__DIR__, 2);
        $directories = array_values(array_filter(
            [
                'templates/components',
                'templates/home',
                'templates/admin',
                'templates/page_content_template',
            ],
            static fn (string $directory): bool => is_dir($projectDirectory.'/'.$directory),
        ));

        self::assertSame([], self::forbiddenDirectoryViolations($directories, 'Legacy template'));
    }

    public function test_guard_reports_legacy_global_template_directories(): void
    {
        self::assertSame(
            [
                'Legacy template directory is forbidden: templates/components.',
                'Legacy template directory is forbidden: templates/home.',
            ],
            self::forbiddenDirectoryViolations(
                ['templates/home', 'templates/components'],
                'Legacy template',
            ),
        );
    }

    public function test_module_shared_is_only_used_by_its_own_module(): void
    {
        $sourceDirectory = dirname(__DIR__, 2).'/src';
        $sources = [];

        foreach (self::phpFilesBelow($sourceDirectory) as $relativeFile) {
            $contents = file_get_contents(dirname(__DIR__, 2).'/'.$relativeFile);

            if ($contents === false) {
                self::fail(sprintf('Unable to read PHP file "%s".', $relativeFile));
            }

            $sources[$relativeFile] = $contents;
        }

        self::assertSame([], self::moduleSharedOwnershipViolations($sources));
    }

    public function test_module_shared_ownership_reports_a_cross_module_reference(): void
    {
        self::assertSame(
            [
                'src/Content/ShowPage/Application/Handler.php references private Contact/Shared code.',
            ],
            self::moduleSharedOwnershipViolations([
                'src/Content/ShowPage/Application/Handler.php' => <<<'PHP'
                    <?php

                    use App\Contact\Shared\Infrastructure\Persistence\ContactMessage;
                    PHP,
            ]),
        );
    }

    /**
     * @param list<string> $directories
     *
     * @return list<string>
     */
    private static function forbiddenDirectoryViolations(array $directories, string $kind): array
    {
        $violations = array_map(
            static fn (string $directory): string => sprintf('%s directory is forbidden: %s.', $kind, $directory),
            $directories,
        );

        sort($violations);

        return $violations;
    }

    /**
     * @param array<string, string> $sources
     *
     * @return list<string>
     */
    private static function moduleSharedOwnershipViolations(array $sources): array
    {
        $violations = [];
        $modulePattern = implode('|', self::BUSINESS_MODULES);

        foreach ($sources as $file => $contents) {
            if (preg_match(sprintf('~^src/(%s)/~', $modulePattern), $file, $consumerMatch) !== 1) {
                continue;
            }

            if (preg_match_all(
                sprintf('~App\\\(%s)\\\Shared(?:\\\|\b)~', $modulePattern),
                $contents,
                $ownerMatches,
            ) === false) {
                throw new \RuntimeException(sprintf('Unable to inspect module references in "%s".', $file));
            }

            foreach (array_unique($ownerMatches[1]) as $owner) {
                if ($owner === $consumerMatch[1]) {
                    continue;
                }

                $violations[] = sprintf('%s references private %s/Shared code.', $file, $owner);
            }
        }

        sort($violations);

        return $violations;
    }

    /**
     * @return list<string>
     */
    private static function phpFilesBelow(string $sourceDirectory): array
    {
        $projectDirectory = dirname(__DIR__, 2);
        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(
            $sourceDirectory,
            \FilesystemIterator::SKIP_DOTS,
        ));

        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo || !$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $files[] = str_replace('\\', '/', substr($file->getPathname(), strlen($projectDirectory) + 1));
        }

        sort($files);

        return $files;
    }
}
