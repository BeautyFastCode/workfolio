<?php

declare(strict_types=1);

namespace App\CallToAction\PublishRandomCta\Application;

interface RandomCtaFinder
{
    public function findRandom(): ?RandomCta;
}
