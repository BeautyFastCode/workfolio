<?php

declare(strict_types=1);

namespace App\Tests\Architecture;

final class CoverageRatchet
{
    private const int BASIS_POINTS_PER_PERCENT = 100;

    /**
     * @return array{covered: int, total: int}
     */
    public static function lineCoverageFromClover(string $file): array
    {
        $document = self::cloverDocument($file);

        $metricNodes = new \DOMXPath($document)->query('/coverage/project/metrics');

        if ($metricNodes === false) {
            throw new \RuntimeException(sprintf('Unable to query Clover report "%s".', $file));
        }

        $metrics = $metricNodes->item(0);

        if (!$metrics instanceof \DOMElement) {
            throw new \RuntimeException(sprintf('Clover report "%s" has no project metrics.', $file));
        }

        return [
            'covered' => self::integerAttribute($metrics, 'coveredstatements', $file),
            'total' => self::integerAttribute($metrics, 'statements', $file),
        ];
    }

    /**
     * @param list<string> $modules
     *
     * @return array<string, array{covered: int, total: int}>
     */
    public static function moduleLineCoverageFromClover(string $file, array $modules): array
    {
        $coverage = [];

        foreach ($modules as $module) {
            $coverage[$module] = ['covered' => 0, 'total' => 0];
        }

        $document = self::cloverDocument($file);
        $xpath = new \DOMXPath($document);
        $fileNodes = $xpath->query('/coverage/project//file');

        if ($fileNodes === false) {
            throw new \RuntimeException(sprintf('Unable to query files in Clover report "%s".', $file));
        }

        foreach ($fileNodes as $fileNode) {
            if (!$fileNode instanceof \DOMElement) {
                continue;
            }

            $path = str_replace('\\', '/', $fileNode->getAttribute('name'));

            if (preg_match('~(?:^|/)src/([^/]+)(?:/|$)~', $path, $matches) !== 1) {
                continue;
            }

            $module = $matches[1];

            if (!array_key_exists($module, $coverage)) {
                continue;
            }

            $metricNodes = $xpath->query('./metrics', $fileNode);

            if ($metricNodes === false || !$metricNodes->item(0) instanceof \DOMElement) {
                throw new \RuntimeException(sprintf('Clover report "%s" has no metrics for "%s".', $file, $path));
            }

            $metrics = $metricNodes->item(0);
            $coverage[$module]['covered'] += self::integerAttribute($metrics, 'coveredstatements', $file);
            $coverage[$module]['total'] += self::integerAttribute($metrics, 'statements', $file);
        }

        return $coverage;
    }

    public static function passes(int $coveredLines, int $totalLines, int $minimumBasisPoints): bool
    {
        if ($totalLines <= 0) {
            throw new \InvalidArgumentException('The total line count must be positive.');
        }

        if ($coveredLines < 0 || $coveredLines > $totalLines) {
            throw new \InvalidArgumentException('The covered line count must be between zero and the total line count.');
        }

        if ($minimumBasisPoints < 0 || $minimumBasisPoints > 100 * self::BASIS_POINTS_PER_PERCENT) {
            throw new \InvalidArgumentException('The minimum coverage must be between 0.00% and 100.00%.');
        }

        return $coveredLines * 100 * self::BASIS_POINTS_PER_PERCENT >= $minimumBasisPoints * $totalLines;
    }

    public static function percentage(int $coveredLines, int $totalLines): float
    {
        if ($totalLines <= 0) {
            throw new \InvalidArgumentException('The total line count must be positive.');
        }

        return $coveredLines / $totalLines * 100;
    }

    private static function cloverDocument(string $file): \DOMDocument
    {
        if (!is_file($file)) {
            throw new \RuntimeException(sprintf('Clover report "%s" does not exist.', $file));
        }

        $document = new \DOMDocument();

        if (!$document->load($file, LIBXML_NONET)) {
            throw new \RuntimeException(sprintf('Clover report "%s" is not valid XML.', $file));
        }

        return $document;
    }

    private static function integerAttribute(\DOMElement $metrics, string $attribute, string $file): int
    {
        $value = $metrics->getAttribute($attribute);

        if (!ctype_digit($value)) {
            throw new \RuntimeException(sprintf(
                'Clover report "%s" has an invalid "%s" project metric.',
                $file,
                $attribute,
            ));
        }

        return (int) $value;
    }
}
