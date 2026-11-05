<?php

declare(strict_types=1);

namespace App\Content\GetLayoutContent\Application;

final readonly class LayoutContentView
{
    /**
     * @param list<LayoutContentBlockView> $blocks
     */
    public function __construct(
        public string $name,
        public array $blocks,
    ) {}

    public function blockContent(string $name): ?string
    {
        return $this->block($name)?->content;
    }

    public function blockImage(string $name): ?string
    {
        return $this->block($name)?->image;
    }

    private function block(string $name): ?LayoutContentBlockView
    {
        foreach ($this->blocks as $block) {
            if ($block->name === $name) {
                return $block;
            }
        }

        return null;
    }
}
