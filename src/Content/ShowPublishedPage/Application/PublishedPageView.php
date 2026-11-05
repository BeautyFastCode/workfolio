<?php

declare(strict_types=1);

namespace App\Content\ShowPublishedPage\Application;

final readonly class PublishedPageView
{
    /**
     * @param list<PublishedPageBlockView> $blocks
     */
    public function __construct(
        public ?string $slug,
        public ?string $title,
        public ?string $menuTitle,
        public string $contentTemplate,
        public array $blocks,
    ) {}

    public function blockContent(string $name): ?string
    {
        foreach ($this->blocks as $block) {
            if ($block->name === $name) {
                return $block->content;
            }
        }

        return null;
    }
}
