<?php

declare(strict_types=1);

namespace App\Content\ListNavigation\UI;

use App\Content\ListNavigation\Application\ListNavigationHandler;
use App\Content\ListNavigation\Application\ListNavigationQuery;
use App\Content\ListNavigation\Application\NavigationItemView;
use App\Shared\Domain\LocaleEnum;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\PostMount;

#[AsTwigComponent(name: 'Navbar', template: 'content/list_navigation/components/Navbar.html.twig')]
final class Navbar
{
    public LocaleEnum $locale = LocaleEnum::EN;

    /**
     * @var list<NavigationItemView>
     */
    public array $pages = [];

    public function __construct(
        private readonly ListNavigationHandler $handler,
        private readonly RequestStack $requestStack,
    ) {}

    #[PostMount]
    public function postMount(): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $slug = $request?->attributes->get('slug');
        $currentPageAvailable = $request !== null && (is_string($slug) || $slug === null);

        $result = ($this->handler)(new ListNavigationQuery(
            $this->locale->value,
            is_string($slug) ? $slug : null,
            $currentPageAvailable,
        ));

        $this->pages = $result->items;
    }
}
