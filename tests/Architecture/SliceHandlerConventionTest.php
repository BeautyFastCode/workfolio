<?php

declare(strict_types=1);

namespace App\Tests\Architecture;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class SliceHandlerConventionTest extends TestCase
{
    // Shared/Application contains cross-cutting contracts, not business use-case slices.
    private const array BUSINESS_MODULES = [
        'Content',
        'Portfolio',
        'Contact',
        'CallToAction',
        'Identity',
    ];

    public function test_each_non_empty_application_slice_declares_exactly_one_handler(): void
    {
        $sourceDirectory = dirname(__DIR__, 2).'/src';

        self::assertSame([], self::handlerCountViolations(self::discoverSlices($sourceDirectory)));
    }

    public function test_convention_reports_a_slice_without_a_handler(): void
    {
        self::assertSame(
            ['Contact/SubmitContactMessage must declare exactly one ...Handler class; found 0.'],
            self::handlerCountViolations([
                'Contact/SubmitContactMessage' => [
                    'SubmitContactMessageCommand',
                    'ContactMessageRepository',
                ],
            ]),
        );
    }

    public function test_convention_reports_a_slice_with_multiple_handlers(): void
    {
        self::assertSame(
            ['Content/ShowPublishedPage must declare exactly one ...Handler class; found 2.'],
            self::handlerCountViolations([
                'Content/ShowPublishedPage' => [
                    'ShowPublishedPageHandler',
                    'CachedShowPublishedPageHandler',
                    'PublishedPageView',
                ],
            ]),
        );
    }

    public function test_convention_discovers_the_target_layout_only(): void
    {
        self::assertSame(
            [
                'Contact/SubmitContactMessage' => ['SubmitContactMessageHandler'],
            ],
            self::discoverSlicesFromApplicationDirectories([
                'Contact/Application/ListContactLinks' => ['ListContactLinksHandler'],
                'Contact/SubmitContactMessage/Application' => ['SubmitContactMessageHandler'],
            ]),
        );
    }

    /**
     * @param array<string, list<string>> $sliceClasses
     *
     * @return list<string>
     */
    private static function handlerCountViolations(array $sliceClasses): array
    {
        $violations = [];

        ksort($sliceClasses);

        foreach ($sliceClasses as $slice => $classes) {
            $handlerCount = count(array_filter(
                $classes,
                static fn (string $class): bool => str_ends_with($class, 'Handler'),
            ));

            if ($handlerCount === 1) {
                continue;
            }

            $violations[] = sprintf(
                '%s must declare exactly one ...Handler class; found %d.',
                $slice,
                $handlerCount,
            );
        }

        return $violations;
    }

    /**
     * @return array<string, list<string>>
     */
    private static function discoverSlices(string $sourceDirectory): array
    {
        $applicationDirectories = [];

        foreach (self::BUSINESS_MODULES as $module) {
            $moduleDirectory = sprintf('%s/%s', $sourceDirectory, $module);

            if (!is_dir($moduleDirectory)) {
                continue;
            }

            $sliceDirectories = new \FilesystemIterator(
                $moduleDirectory,
                \FilesystemIterator::SKIP_DOTS,
            );

            foreach ($sliceDirectories as $sliceDirectory) {
                if (
                    !$sliceDirectory instanceof \SplFileInfo
                    || !$sliceDirectory->isDir()
                    || in_array($sliceDirectory->getFilename(), ['Application', 'Infrastructure', 'UI', 'Shared'], true)
                ) {
                    continue;
                }

                $applicationDirectory = $sliceDirectory->getPathname().'/Application';

                if (!is_dir($applicationDirectory)) {
                    continue;
                }

                $applicationDirectories[sprintf(
                    '%s/%s/Application',
                    $module,
                    $sliceDirectory->getFilename(),
                )] = self::classNamesBelow($applicationDirectory);
            }
        }

        return self::discoverSlicesFromApplicationDirectories($applicationDirectories);
    }

    /**
     * @param array<string, list<string>> $applicationDirectories
     *
     * @return array<string, list<string>>
     */
    private static function discoverSlicesFromApplicationDirectories(array $applicationDirectories): array
    {
        $slices = [];

        foreach ($applicationDirectories as $directory => $classes) {
            if ($classes === []) {
                continue;
            }

            $segments = explode('/', $directory);
            $applicationIndex = array_search('Application', $segments, true);

            if ($applicationIndex === 2) {
                $slices[$segments[0].'/'.$segments[1]] = $classes;
            }
        }

        ksort($slices);

        return $slices;
    }

    /**
     * @return list<string>
     */
    private static function classNamesBelow(string $directory): array
    {
        $classNames = [];
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(
            $directory,
            \FilesystemIterator::SKIP_DOTS,
        ));

        foreach ($files as $file) {
            if (!$file instanceof \SplFileInfo || !$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            array_push($classNames, ...self::classNamesIn($file->getPathname()));
        }

        return $classNames;
    }

    /**
     * @return list<string>
     */
    private static function classNamesIn(string $file): array
    {
        $contents = file_get_contents($file);

        if ($contents === false) {
            throw new \RuntimeException(sprintf('Unable to read PHP file "%s".', $file));
        }

        $tokens = token_get_all($contents);
        $classNames = [];

        foreach ($tokens as $index => $token) {
            if (!is_array($token) || $token[0] !== T_CLASS) {
                continue;
            }

            for ($nextIndex = $index + 1, $tokenCount = count($tokens); $nextIndex < $tokenCount; ++$nextIndex) {
                $nextToken = $tokens[$nextIndex];

                if (is_array($nextToken) && in_array($nextToken[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                    continue;
                }

                if (is_array($nextToken) && $nextToken[0] === T_STRING) {
                    $classNames[] = $nextToken[1];
                }

                break;
            }
        }

        return $classNames;
    }
}
