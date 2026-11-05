<?php

declare(strict_types=1);

namespace App\Content\GetLayoutContent\UI;

use App\Content\GetLayoutContent\Application\GetLayoutContentHandler;
use App\Content\GetLayoutContent\Application\GetLayoutContentQuery;
use App\Content\GetLayoutContent\Application\LayoutContentView;
use App\Shared\UI\Twig\LocaleProvider;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\PostMount;

#[AsTwigComponent(name: 'Header', template: 'content/get_layout_content/components/Header.html.twig')]
final class Header
{
    public ?LayoutContentView $data = null;

    public function __construct(
        private readonly GetLayoutContentHandler $handler,
        private readonly LocaleProvider $localeProvider,
    ) {}

    #[PostMount]
    public function postMount(): void
    {
        $this->data = ($this->handler)(new GetLayoutContentQuery(
            'header',
            $this->localeProvider->getCurrent()->value,
        ));
    }
}
