<?php

declare(strict_types=1);

namespace App\Tests\Architecture;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class CoverageRatchetTest extends TestCase
{
    private const int MINIMUM_BASIS_POINTS = 1988;

    public function test_line_coverage_below_the_baseline_fails(): void
    {
        self::assertFalse(CoverageRatchet::passes(1987, 10_000, self::MINIMUM_BASIS_POINTS));
    }

    public function test_line_coverage_equal_to_the_baseline_passes(): void
    {
        self::assertTrue(CoverageRatchet::passes(497, 2_500, self::MINIMUM_BASIS_POINTS));
    }

    public function test_line_coverage_above_the_baseline_passes(): void
    {
        self::assertTrue(CoverageRatchet::passes(1989, 10_000, self::MINIMUM_BASIS_POINTS));
    }

    public function test_module_coverage_is_aggregated_and_files_outside_modules_are_ignored(): void
    {
        self::assertSame([
            'Content' => ['covered' => 9, 'total' => 10],
            'Contact' => ['covered' => 7, 'total' => 8],
            'Portfolio' => ['covered' => 0, 'total' => 0],
        ], CoverageRatchet::moduleLineCoverageFromClover(
            dirname(__DIR__).'/Fixtures/Coverage/module-clover.xml',
            ['Content', 'Contact', 'Portfolio'],
        ));
    }
}
