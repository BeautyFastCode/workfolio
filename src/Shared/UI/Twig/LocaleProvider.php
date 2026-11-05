<?php

declare(strict_types=1);

namespace App\Shared\UI\Twig;

use App\Shared\Domain\LocaleEnum;
use Symfony\Component\HttpFoundation\RequestStack;

final readonly class LocaleProvider
{
    public function __construct(
        private RequestStack $requestStack,
    ) {}

    public function getCurrent(): LocaleEnum
    {
        $locale = $this->requestStack->getCurrentRequest()?->getLocale();

        if ($locale === null) {
            throw new \RuntimeException('Current locale is not set.');
        }

        return LocaleEnum::tryFrom($locale) ?? LocaleEnum::EN;
    }
}
