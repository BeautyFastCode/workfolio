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
final class TranslationCatalogConventionTest extends TestCase
{
    public function test_translation_keys_are_unique_and_have_locale_parity(): void
    {
        $translationDirectory = dirname(__DIR__, 2).'/translations';
        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(
            $translationDirectory,
            \FilesystemIterator::SKIP_DOTS,
        ));

        foreach ($iterator as $file) {
            if (
                !$file instanceof \SplFileInfo
                || !$file->isFile()
                || preg_match('~\.ya?ml$~', $file->getFilename()) !== 1
            ) {
                continue;
            }

            $contents = file_get_contents($file->getPathname());

            if ($contents === false) {
                self::fail(sprintf('Unable to read translation file "%s".', $file->getPathname()));
            }

            $files[str_replace(
                '\\',
                '/',
                substr($file->getPathname(), strlen($translationDirectory) + 1),
            )] = $contents;
        }

        self::assertSame([], self::catalogViolations($files));
    }

    public function test_guard_reports_a_duplicate_key_across_module_files(): void
    {
        self::assertSame(
            [
                'Duplicate messages.en key "contact_message.submit" in '
                .'Contact/messages.en.yaml and Shared/messages.en.yaml.',
                'Duplicate messages.pl key "contact_message.submit" in '
                .'Contact/messages.pl.yaml and Shared/messages.pl.yaml.',
            ],
            self::catalogViolations([
                'Contact/messages.en.yaml' => "contact_message:\n    submit: Send\n",
                'Contact/messages.pl.yaml' => "contact_message:\n    submit: Wyślij\n",
                'Shared/messages.en.yaml' => "contact_message:\n    submit: Duplicate\n",
                'Shared/messages.pl.yaml' => "contact_message:\n    submit: Duplikat\n",
            ]),
        );
    }

    public function test_guard_reports_missing_locale_parity(): void
    {
        self::assertSame(
            ['messages.pl is missing key "button.more" present in messages.en.'],
            self::catalogViolations([
                'Shared/messages.en.yaml' => "site_name: Workfolio\nbutton:\n    more: More\n",
                'Shared/messages.pl.yaml' => "site_name: Workfolio\n",
            ]),
        );
    }

    /**
     * @param array<string, string> $files
     *
     * @return list<string>
     */
    private static function catalogViolations(array $files): array
    {
        /** @var array<string, array<string, array<string, string>>> $catalogs */
        $catalogs = [];
        $violations = [];

        ksort($files);

        foreach ($files as $file => $contents) {
            if (preg_match('~(?:^|/)([^/]+)\.([^.]+)\.ya?ml$~', $file, $matches) !== 1) {
                continue;
            }

            $domain = $matches[1];
            $locale = $matches[2];
            $messages = Yaml::parse($contents);

            if (!is_array($messages)) {
                throw new \RuntimeException(sprintf('Translation file "%s" must contain a YAML mapping.', $file));
            }

            foreach (self::flattenKeys($messages) as $key) {
                if (isset($catalogs[$domain][$locale][$key])) {
                    $violations[] = sprintf(
                        'Duplicate %s.%s key "%s" in %s and %s.',
                        $domain,
                        $locale,
                        $key,
                        $catalogs[$domain][$locale][$key],
                        $file,
                    );

                    continue;
                }

                $catalogs[$domain][$locale][$key] = $file;
            }
        }

        foreach ($catalogs as $domain => $locales) {
            $englishKeys = array_keys($locales['en'] ?? []);
            $polishKeys = array_keys($locales['pl'] ?? []);

            foreach (array_diff($englishKeys, $polishKeys) as $key) {
                $violations[] = sprintf(
                    '%s.pl is missing key "%s" present in %s.en.',
                    $domain,
                    $key,
                    $domain,
                );
            }

            foreach (array_diff($polishKeys, $englishKeys) as $key) {
                $violations[] = sprintf(
                    '%s.en is missing key "%s" present in %s.pl.',
                    $domain,
                    $key,
                    $domain,
                );
            }
        }

        sort($violations);

        return $violations;
    }

    /**
     * @param array<array-key, mixed> $messages
     *
     * @return list<string>
     */
    private static function flattenKeys(array $messages, string $prefix = ''): array
    {
        $keys = [];

        foreach ($messages as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;

            if (is_array($value)) {
                array_push($keys, ...self::flattenKeys($value, $path));

                continue;
            }

            $keys[] = $path;
        }

        return $keys;
    }
}
