<?php

declare(strict_types=1);

namespace App\Shared\UI\Twig\Extension;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

final class AnalyticsExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        #[Autowire(param: 'umami.enabled')]
        private readonly bool $umamiEnabled,
        #[Autowire(param: 'umami.url')]
        private readonly string $umamiUrl,
        #[Autowire(param: 'umami.website_id')]
        private readonly string $umamiWebsiteId
    ) {}

    public function getGlobals(): array
    {
        return [
            'analytics' => [
                'enabled' => $this->umamiEnabled,
                'url' => $this->umamiUrl,
                'website_id' => $this->umamiWebsiteId,
            ],
        ];
    }
}
